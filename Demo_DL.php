<?php 

	abstract class Demo_DL extends QueryBuilder{
		protected function FetchAllData(){
			$this->from('table');
			$this->FetchRecords();

			return $data;
		}
	}