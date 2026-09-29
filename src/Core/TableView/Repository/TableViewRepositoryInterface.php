<?php
namespace Skeletor\Core\TableView\Repository;

interface TableViewRepositoryInterface
{
    public function fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter = null);

    public function getTotalCount();

    public function getSearchableColumns();
}