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
       $this->hour_trading_day_ends = 15;

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

		//Lets get a trend!
		$this->calculate_trend($ticker_symbol,$sixdaysago,$this->today);
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
		//Display the daychart of a given stock
		//lets check again to make sure we have the price in the database
		$this->check_snapshot($ticker_symbol,$date);
		$this->display_hloc_daily($ticker_symbol,$date);
	}
	public function display_hloc_daily($ticker_symbol, $date) {
		$conn = $this->db_connection;
		$sql = "select * from stock_snapshot where ticker_symbol = '$ticker_symbol' and date_captured = '$date';";
		$result = mysqli_query($conn,$sql);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		
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
    public function calculate_price_movement($ticker_symbol,$date) {
    	$conn = $this->db_connection;
		$sql = "select * from stock_snapshot where ticker_symbol = '$ticker_symbol' and date_captured = '$date';";
		$result = mysqli_query($conn,$sql);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		return $row['open'] - $row['close'];
    }
    public function calculate_percent_difference($num1,$num2) {
    	$v1 = $num1;
    	$v2 = $num2;

    	return abs($v1 - $v2) / (($v1 + $v2)/2) * 100;
    }

    public function calculate_trend($ticker_symbol,$date_begin,$date_end) {
    	$date_begin_i = new DateTime($date_begin);
		$date_end_e = new DateTime($date_end);
		$price_stack = [];

		for($i=$date_begin_i;$i<=$date_end_e;$i->modify('+1 day')) {
			$day_of_week = date('l',strtotime($i->format('Y-m-d')));
			
			if($day_of_week == 'Sunday' || $day_of_week == 'Saturday') {
				
				continue;
			}
			$conn = $this->db_connection;
			$date = $i->format('Y-m-d');
			$sql = "select * from stock_snapshot where ticker_symbol = '$ticker_symbol' and date_captured = '$date';";
			$result = mysqli_query($conn,$sql);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			if($row['close'] != NULL) {
				array_push($price_stack, $row['close']);
			}
		}
       
        $start = $price_stack[0];
        $end = end($price_stack);
        echo 'Start: ' . $start;
        echo 'End: ' .$end;
        //baseline trend read
        if($start > $end) {
        	
        	echo "Trend is negative. Down from six days ago by " . $this->calculate_percent_difference($start,$end) . " percent";
        }
        else {
        	echo "Trend is postive. Up from six days ago by " . $this->calculate_percent_difference($start,$end) . "percent";
        }
		
		//obviously more calculating will come
    }

	public function display_chart_daterange($ticker_symbol,$date_begin,$date_end) {
		//declare a dates array to hold all the dates we will be looking at
		
		$date_begin_i = new DateTime($date_begin);
		$date_end_e = new DateTime($date_end);
		echo 'running date range <br />';
		echo "<div style='border:solid 1px black; width:200px;';>";
		echo 'Date Begin: ' . $date_begin . "<br />";
		echo 'Date End: ' . $date_end . "<br />";  
		echo "</div>";
	
		for($i=$date_begin_i;$i<=$date_end_e;$i->modify('+1 day')) {
			echo $i->format('Y-m-d') . "<br />";
			$day_of_week = date('l',strtotime($i->format('Y-m-d')));
			echo $day_of_week;
			if($day_of_week == 'Sunday' || $day_of_week == 'Saturday') {
				echo 'Weekend Date Detected/Not Bothering to check';
				continue;
			}
			$this->check_snapshot($ticker_symbol,$i->format('Y-m-d'));
			$this->display_hloc_daily($ticker_symbol, $i->format('Y-m-d'));
			
		}
		
		}

		
	}
//end of analyzer class







