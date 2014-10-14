<?php
ini_set('display_errors','On');

class ServerProperties {

	public function is_post() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return 1;
		} 
		else {
			return null;
		}
	}
}

class PostAccess {
	
	public function check_ticker($ticker_symbol) {
		if($ticker_symbol == "" || !isset($ticker_symbol)) {
			return 0;
		}
		
		return 1;
	}

	public function get_ticker_symbol(){
		$ticker_symbol = trim($_POST['ticker_name']);

		$result = $this->check_ticker($ticker_symbol);

		if($result == 1) {
			return $ticker_symbol;
		}
		else {
			return 'FAIL';
		}



	}
}

class Analyzer {
	public $today;
	public $hour_trading_day_ends;
	public $db_connection;


	function __construct() {
       $this->today = date('Y-m-d');
       $this->hour_trading_day_ends = 16;

       //Lets get one single connection to the database, so we dont constantly re connect to the same thing.
       $this->db_connection = $this->db_connect();
    }

	public function db_connect() {
		$username = "root";
		$password = "jonjon";
		$hostname = "localhost"; 

		//connection to the database
		$dbhandle = mysqli_connect($hostname,$username,$password,"abbastoons_stock");
		//echo "Connected to MySQL<br>";
		return $dbhandle;
	}

	public function days_ago($days) {
		return date('Y-m-d',strtotime($this->today . '- ' . "$days" . ' day'));
	}

	public function analyze($ticker_symbol) {
		
		echo "Analyzing: " . "<b>$ticker_symbol</b>" . "<br />";
		echo $this->today;
		
		$sixdaysago = $this->days_ago(6);
		echo "Analysis Report for <b>" . $ticker_symbol . "</b> for the date of <b>" . $this->today . "</b><br />";
		if (date('H') <= $this->hour_trading_day_ends) { 
		echo 'Trading day has not concluded - unable to show hi,lo or close! <br />';
		 }
		 else {
		 	$this->display_daychart($ticker_symbol, $this->today);
		 }
		//Start analyzing the stock by displaying a day chart and a range to start to get a trend established		
		
		$this->display_chart_daterange($ticker_symbol,$sixdaysago,$this->today);
	}

	public function check_snapshot($ticker_symbol,$date) {
		echo "Checking to see if we have the snapshot for $ticker_symbol in our database...";
		$conn = $this->db_connection;
		$sql = "select * from stock_snapshot where ticker_symbol = '$ticker_symbol' and date_captured = '$date';";
		//echo $sql;
		$result = mysqli_query($conn,$sql);
	
	
		$num_rows = mysqli_num_rows($result);
		if($num_rows == 0) {
			echo "need to get a snapshot <br />";
		
			$date_begin_ind = explode("-",$date);
			$begin_month = $date_begin_ind[1] -1;
        	$begin_day = $date_begin_ind[2];
        	$begin_year = $date_begin_ind[0];
			$url = "http://ichart.yahoo.com/table.csv?s=$ticker_symbol&a=$begin_month&b=$begin_day&c=$begin_year&d=$begin_month&e=$begin_day&f=$begin_year&g=d";
			echo $url;
	    	$file_handle = @fopen($url, "r");
			if ( $file_handle == FALSE ) { // If the URL can't be opened
				$error_message = "Cannot get data from Yahoo! Finance. The following URL is not accessible, $url";
			return -1; // ERROR
		}
		$hi="";
		$low="";
		$open="";
		$close="";
		$row = 0;
	    while (!feof($file_handle) ) {
         $line_of_text = fgetcsv($file_handle, 1024);
         if($row==0) {
         	$row++;
         	continue;

         }
         	if($line_of_text[0] != "") {
         		$open=$line_of_text[1];
         	    $hi = $line_of_text[2];
         	    $low= $line_of_text[3];
         	   	$close=$line_of_text[4];    
         	}    
         }
		
		echo "validating snapshot... <br />";
		
    	if($hi != "" && $low != "" && $open != "" && $close != "") { 
    		echo "snap is good capturing into our database <br />";
   		 	$this->capture_snapshot($ticker_symbol,$date,$hi,$low,$open,$close);
   		 }
   		 else {

   		 	die("snapshot invalid; ticker symbol is most likly not valid");
   		 }

		
	}
	else {
		echo "we already got the snap! <br />";
	}
	

	}
	public function display_daychart($ticker_symbol, $date) {
		$conn = $this->db_connection;
		//Display the daychart of a given stock
		//lets check again to make sure we have the price in the database
		$this->check_snapshot($ticker_symbol,$date);
		$sql = "select * from stock_snapshot where ticker_symbol = '$ticker_symbol' and date_captured = '$date';";
		$result = mysqli_query($conn,$sql);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$day_of_week = date('l',$date);
		echo "<b>Date:</b> " . $date . "<br /> Day of Week:" . date('l',strtotime($date)) . "<br />";
		echo "<br /><b>High:</b> " . $row['hi'] . "<br />";
		echo "<b>Low:</b> " . $row['low'] . "<br />";
		echo "<b>Open:</b> " . $row['open'] . "<br />";
		echo "<b>Close:</b> " . $row['close'] . "<br />";
	}

	public function capture_snapshot($ticker_symbol,$date,$hi,$lo,$open,$close) {
		$conn = $this->db_connection;
		$sql = "insert into stock_snapshot values(DEFAULT,'$ticker_symbol','$date',$hi,$lo,$open,$close);";
		//echo $sql;
		mysqli_query($conn,$sql);
    }

	public function display_chart_daterange($ticker_symbol,$date_begin,$date_end) {
		//declare a dates array to hold all the dates we will be looking at
		$dates = array();
		echo 'running date range <br />';
		echo "<div style='border:solid 1px black; width:200px;';>";
		echo 'Date Begin: ' . $date_begin . "<br />";
		echo 'Date End: ' . $date_end . "<br />";  
		echo "</div>";
	
		echo 'usings these values';
		$date_begin_ind = explode("-",$date_begin);
		$date_end_ind = explode("-",$date_end);

    	$begin_month = $date_begin_ind[1] -1;
    	$begin_day = $date_begin_ind[2];
   		$begin_year = $date_begin_ind[0];

    	$end_month = $date_end_ind[1] -1;
    	$end_day = $date_end_ind[2];
    	$end_year = $date_end_ind[0];
    
		$url = "http://ichart.yahoo.com/table.csv?s=$ticker_symbol&a=$begin_month&b=$begin_day&c=$begin_year&d=$end_month&e=$end_day&f=$end_year&g=d";
		echo $url;
		$row = 0;
		$file_handle = @fopen($url, "r");

		if ( $file_handle == FALSE ) { // If the URL can't be opened
			$error_message = "Cannot get data from Yahoo! Finance. The following URL is not accessible, $url";
			return -1; // ERROR
		}

		while (!feof($file_handle) ) {
			$line_of_text = fgetcsv($file_handle, 1024);
			
			if($row == 0 ) { 
				echo 'headers found <br />';
				$row++;
				continue;
			}

			if($line_of_text[0] == "") {
				echo 'empty row detected <br />';
				continue;
			}


			echo "<b>Date</b> "  . $line_of_text[0] . "<br /><br />";

			echo "<b>Open</b>" . $line_of_text[1]. "<br />";
			echo "<b>High</b>" . $line_of_text[2] . "<br />";
			echo "<b>Low</b>" . $line_of_text[3] . "<br />";
			echo "<b>Close</b>" . $line_of_text[4] . "<br /><br />";

		}

		fclose($file_handle);

	}
//end of analyzer class

}





