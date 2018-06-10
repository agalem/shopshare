<?php
namespace Utils;

use Doctrine\DBAL\Query\QueryBuilder;

class Paginator {

	protected $queryBuilder;

	protected $countQueryBuilder;

	protected $currentPage = 1;

	protected $maxPerPage = 1;

	public function __construct(QueryBuilder $queryBuilder, QueryBuilder $countQueryBuilder) {

		$this->queryBuilder = $queryBuilder;
		$this->countQueryBuilder = $countQueryBuilder;

	}

	public function setCurrentPage($currentPage) {
		$this->currentPage = (integer) ((ctype_digit((string) $currentPage) && $currentPage > 0) ? $currentPage : 1);
	}

	public function setMaxPerPage($maxPerPage) {
		$this->maxPerPage = (integer) ((ctype_digit((string) $maxPerPage) && $maxPerPage > 0) ? $maxPerPage : 1);
	}

	public function getCurrentPageResults()  {
		$pagesNumber = $this->countAllPages();

		return [
			'page' => $this->calculateCurrentPageNumber($pagesNumber),
			'max_result' => $this->maxPerPage,
			'pages_number' => $pagesNumber,
			'data' => $this->findData(),
		];
	}

	protected function findData() {
		$this->queryBuilder->setFirstResult(
			($this->currentPage  - 1) * $this->maxPerPage
		);

		$this->queryBuilder->setMaxResults($this->maxPerPage);

		return $this->queryBuilder->execute()->fetchAll();
	}

	protected function countAllPages() {
		$result = $this->countQueryBuilder->execute()->fetch();

		if($result) {
			$pagesNumber = ceil($result['total_results'] / $this->maxPerPage);
		} else {
			$pagesNumber = 1;
		}

		return $pagesNumber;
	}

	protected  function calculateCurrentPageNumber($pagesNumber) {
		return ($this->currentPage < 1 || $this->currentPage > $pagesNumber ) ? 1: $this->currentPage;
	}

}