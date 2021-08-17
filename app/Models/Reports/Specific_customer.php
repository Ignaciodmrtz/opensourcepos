<?php

namespace App\Models\Reports;

use CodeIgniter\Model;

require_once("Report.php");

class Specific_customer extends Report
{
	public function create(array $inputs)
	{
		//Create our temp tables to work with the data in our report
		$this->Sale->create_temp_table($inputs);
	}

	public function getDataColumns()
	{
		return array(
			'summary' => array(
				array('id' => lang('Reports.sale_id')),
				array('type_code' => lang('Reports.code_type')),
				array('sale_date' => lang('Reports.date'), 'sortable' => FALSE),
				array('quantity' => lang('Reports.quantity')),
				array('employee_name' => lang('Reports.sold_by')),
				array('subtotal' => lang('Reports.subtotal'), 'sorter' => 'number_sorter'),
				array('tax' => lang('Reports.tax'), 'sorter' => 'number_sorter'),
				array('total' => lang('Reports.total'), 'sorter' => 'number_sorter'),
				array('cost' => lang('Reports.cost'), 'sorter' => 'number_sorter'),
				array('profit' => lang('Reports.profit'), 'sorter' => 'number_sorter'),
				array('payment_type' => lang('Reports.payment_type'), 'sortable' => FALSE),
				array('comment' => lang('Reports.comments'))),
			'details' => array(
				lang('Reports.name'),
				lang('Reports.category'),
				lang('Reports.item_number'),
				lang('Reports.description'),
				lang('Reports.quantity'),
				lang('Reports.subtotal'),
				lang('Reports.tax'),
				lang('Reports.total'),
				lang('Reports.cost'),
				lang('Reports.profit'),
				lang('Reports.discount')),
			'details_rewards' => array(
				lang('Reports.used'),
				lang('Reports.earned'))
		);
	}

	public function getData(array $inputs)
	{
		$this->db->select('sale_id,
			MAX(CASE
			WHEN sale_type = ' . SALE_TYPE_POS . ' && sale_status = ' . COMPLETED . ' THEN \'' . lang('Reports.code_pos') . '\'
			WHEN sale_type = ' . SALE_TYPE_INVOICE . ' && sale_status = ' . COMPLETED . ' THEN \'' . lang('Reports.code_invoice') . '\'
			WHEN sale_type = ' . SALE_TYPE_WORK_ORDER . ' && sale_status = ' . SUSPENDED . ' THEN \'' . lang('Reports.code_work_order') . '\'
			WHEN sale_type = ' . SALE_TYPE_QUOTE . ' && sale_status = ' . SUSPENDED . ' THEN \'' . lang('Reports.code_quote') . '\'
			WHEN sale_type = ' . SALE_TYPE_RETURN . ' && sale_status = ' . COMPLETED . ' THEN \'' . lang('Reports.code_return') . '\'
			WHEN sale_status = ' . CANCELED . ' THEN \'' . lang('Reports.code_canceled') . '\'
			ELSE \'\'
			END) AS type_code,
			MAX(sale_status) as sale_status,
			MAX(sale_date) AS sale_date,
			SUM(quantity_purchased) AS items_purchased,
			MAX(employee_name) AS employee_name,
			SUM(subtotal) AS subtotal,
			SUM(tax) AS tax,
			SUM(total) AS total,
			SUM(cost) AS cost,
			SUM(profit) AS profit,
			MAX(payment_type) AS payment_type,
			MAX(comment) AS comment');
		$builder = $this->db->table('sales_items_temp');

		$builder->where('customer_id', $inputs['customer_id']);

		if($inputs['payment_type'] == 'invoices')
		{
			$builder->where('sale_type', SALE_TYPE_INVOICE);
		}
		elseif($inputs['payment_type'] != 'all')
		{
			$this->db->like('payment_type', lang('Sales.'.$inputs['payment_type']));
		}

		if($inputs['sale_type'] == 'complete')
		{
			$builder->where('sale_status', COMPLETED);
			$this->db->group_start();
			$builder->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->or_where('sale_type', SALE_TYPE_RETURN);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'sales')
		{
			$builder->where('sale_status', COMPLETED);
			$this->db->group_start();
			$builder->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'quotes')
		{
			$builder->where('sale_status', SUSPENDED);
			$builder->where('sale_type', SALE_TYPE_QUOTE);
		}
		elseif($inputs['sale_type'] == 'work_orders')
		{
			$builder->where('sale_status', SUSPENDED);
			$builder->where('sale_type', SALE_TYPE_WORK_ORDER);
		}
		elseif($inputs['sale_type'] == 'canceled')
		{
			$builder->where('sale_status', CANCELED);
		}
		elseif($inputs['sale_type'] == 'returns')
		{
			$builder->where('sale_status', COMPLETED);
			$builder->where('sale_type', SALE_TYPE_RETURN);
		}

		$this->db->group_by('sale_id');
		$builder->orderBy('MAX(sale_date)');

		$data = array();
		$data['summary'] = $builder->get()->result_array();
		$data['details'] = array();
		$data['rewards'] = array();

		foreach($data['summary'] as $key=>$value)
		{
			$this->db->select('name, category, item_number, description, quantity_purchased, subtotal, tax, total, cost, profit, discount, discount_type');
			$builder = $this->db->table('sales_items_temp');
			$builder->where('sale_id', $value['sale_id']);
			$data['details'][$key] = $builder->get()->result_array();
			$this->db->select('used, earned');
			$builder = $this->db->table('sales_reward_points');
			$builder->where('sale_id', $value['sale_id']);
			$data['rewards'][$key] = $builder->get()->result_array();
		}

		return $data;
	}

	public function getSummaryData(array $inputs)
	{
		$this->db->select('SUM(subtotal) AS subtotal, SUM(tax) AS tax, SUM(total) AS total, SUM(cost) AS cost, SUM(profit) AS profit');
		$builder = $this->db->table('sales_items_temp');

		$builder->where('customer_id', $inputs['customer_id']);

		if($inputs['payment_type'] == 'invoices')
		{
			$builder->where('sale_type', SALE_TYPE_INVOICE);
		}
		elseif ($inputs['payment_type'] != 'all')
		{
			$this->db->like('payment_type', lang('Sales.'.$inputs['payment_type']));
		}

		if($inputs['sale_type'] == 'complete')
		{
			$builder->where('sale_status', COMPLETED);
			$this->db->group_start();
			$builder->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->or_where('sale_type', SALE_TYPE_RETURN);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'sales')
		{
			$builder->where('sale_status', COMPLETED);
			$this->db->group_start();
			$builder->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'quotes')
		{
			$builder->where('sale_status', SUSPENDED);
			$builder->where('sale_type', SALE_TYPE_QUOTE);
		}
		elseif($inputs['sale_type'] == 'work_orders')
		{
			$builder->where('sale_status', SUSPENDED);
			$builder->where('sale_type', SALE_TYPE_WORK_ORDER);
		}
		elseif($inputs['sale_type'] == 'canceled')
		{
			$builder->where('sale_status', CANCELED);
		}
		elseif($inputs['sale_type'] == 'returns')
		{
			$builder->where('sale_status', COMPLETED);
			$builder->where('sale_type', SALE_TYPE_RETURN);
		}

		return $builder->get()->row_array();
	}
}
?>