<?php 
	class Demo_BL extends Demo_DL{
		public function AllData(){
			$data = new FetchAllData();
			$finalData = array();
			foreach ($data as $key => $record) {
				$EL = new Demo_EL();

				$EL = fill($record);

				$finalData[$key] = $EL;
			}
			return $finalData;
		}
	}