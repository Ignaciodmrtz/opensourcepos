<?php

namespace App\Models\Reports;

use CodeIgniter\Model;

require_once("Summary_report.php");

class Summary_sales extends Summary_report
{
	protected function _get_data_columns()
	{
		return array(
			array('sale_date' => lang('Reports.date'), 'sortable' => FALSE),
			array('sales' => lang('Reports.sales'), 'sorter' => 'number_sorter'),
			array('quantity' => lang('Reports.quantity'), 'sorter' => 'number_sorter'),
			array('subtotal' => lang('Reports.subtotal'), 'sorter' => 'number_sorter'),
			array('tax' => lang('Reports.tax'), 'sorter' => 'number_sorter'),
			array('total' => lang('Reports.total'), 'sorter' => 'number_sorter'),
			array('cost' => lang('Reports.cost'), 'sorter' => 'number_sorter'),
			array('profit' => lang('Reports.profit'), 'sorter' => 'number_sorter'));
	}

	protected function _select(array $inputs)
	{
		parent::_select($inputs);

		$this->db->select('
				DATE(sales.sale_time) AS sale_date,
				SUM(sales_items.quantity_purchased) AS quantity_purchased,
				COUNT(DISTINCT sales.sale_id) AS sales
		');
	}

	protected function _group_order()
	{
		$this->db->group_by('sale_date');
		$builder->orderBy('sale_date');
	}
}
?>