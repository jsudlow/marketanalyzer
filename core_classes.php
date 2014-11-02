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

	public function read_yahoo_single_ohlc($ticker_symbol,$date) {
			$date_begin_ind = explode("-",$date);
			$begin_month = $date_begin_ind[1] -1;
        	$begin_day = $date_begin_ind[2];
        	$begin_year = $date_begin_ind[0];
			$url = "http://ichart.yahoo.com/table.csv?s=$ticker_symbol&a=$begin_month&b=$begin_day&c=$begin_year&d=$begin_month&e=$begin_day&f=$begin_year&g=d";
			//echo $url;
	    	$file_handle = @fopen($url, "r");
			if ( $file_handle == FALSE ) { // If the URL can't be opened
				echo "Cannot get data from Yahoo! Finance. The following URL is not accessible, $url";
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
         	$return_array = array("open"=>$open,"hi"=>$hi,"low"=>$low,"close"=>$close);
         	
         	return $return_array;

	}
	public function validate_yahoo_single_ohlc($ticker_symbol,$ohlc_array,$date) {
		echo "validating snap... <br />";

		$open = $ohlc_array['open'];
		$hi = $ohlc_array['hi'];
		$low = $ohlc_array['low'];
		$close = $ohlc_array['close'];
    	
    	if($open != "" && $hi != "" && $low != "" && $close != "") { 
    		echo "snap is good<br />";
   			$this->capture_snapshot($ticker_symbol,$date,$hi,$low,$open,$close);
   		}
   		else {
   			die("snapshot invalid; ticker symbol is most likly not valid");
   		}

	}
	public function check_snapshot($ticker_symbol,$date) {
		echo "Checking for snap in db <br />";
		$conn = $this->db_connection;
		$sql = "select * from stock_snapshot where ticker_symbol = '$ticker_symbol' and date_captured = '$date';";
		//echo $sql;
		$result = mysqli_query($conn,$sql);
	
		$num_rows = mysqli_num_rows($result);
		
		if($num_rows == 0) {
			echo "need snap<br />";
			$ohlc_array = $this->read_yahoo_single_ohlc($ticker_symbol,$date);
			$this->validate_yahoo_single_ohlc($ticker_symbol,$ohlc_array,$date);		
		}
		else {
				echo "we already got the snap! <br />";
		}
		

	}
	public function display_daychart($ticker_symbol,$date) {
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
		
		echo "<div style='border: 1px solid black; width:200px;'><b>Date:</b> " . $date . "<br /> Day of Week:" . date('l',strtotime($date)) . "<br />";
		echo "<br /><b>High:</b> " . $row['hi'] . "<br />";
		echo "<b>Low:</b> " . $row['low'] . "<br />";
		echo "<b>Open:</b> " . $row['open'] . "<br />";
		echo "<b>Close:</b> " . $row['close'] . "<br /></div><br />";
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

    	$percent_difference = abs($v1 - $v2) / (($v1 + $v2)/2) * 100;
    	return number_format((float)$percent_difference, 2, '.', '');
    }

    public function reverse_date_to_last_trading_day($date) {
    	$day_of_week = date('l',strtotime($date));
		if($day_of_week == 'Sunday') {
			return new DateTime(date('Y-m-d', strtotime('-2 day', strtotime($date))));

		} 
		if($day_of_week == 'Saturday') {
			return new DateTime(date('Y-m-d', strtotime('-1 day', strtotime($date))));
    	}
    	
    }
    public function calculate_trend($ticker_symbol,$date_begin,$date_end) {
    	$start_date = new DateTime($date_begin);
    	$end_date = new DateTime($date_end);
    	    	
    	//Lets check the start date to see if its on a weekend or holiday
    	//If it is we will reverse the day to the last known valid trading day
    	if($this->check_weekend_and_holiday_dates($start_date)) {
    		echo 'start date has popped for weekend use';
    		$start_date = $this->reverse_date_to_last_trading_day($start_date->format('Y-m-d'));
    	}
    	
        //Lets do the same checks with the end date
        if($this->check_weekend_and_holiday_dates($end_date)) {
        	echo 'end date has popped for weekend use';
    		$end_date = $this->reverse_date_to_last_trading_day($end_date->format('Y-m-d'));
    	}

    	//Now we have a valid day lets check to see if we got the snap in our database
    	$this->check_snapshot($ticker_symbol,$start_date->format('Y-m-d'));
        $this->check_snapshot($ticker_symbol,$end_date->format('Y-m-d'));

        //Now that we got ours snap in our db we can assign the accurate open and closes 
        $start = $this->get_close_by_date($ticker_symbol,$start_date->format('Y-m-d'));
        $end = $this->get_close_by_date($ticker_symbol,$end_date->format('Y-m-d'));

        
        //Now we can print the trend report
        echo "Start: (" . $start_date->format('Y-m-d') . ") <b>" . $start . "</b><br />";
        echo "End: (" . $end_date->format('Y-m-d') . ") <b>" . $end . "</b><br />";
        //baseline trend read
        if($start > $end) {
        	
        	echo "Trend is <span style='color:red;'>negative</span>.<br /> Down from six days ago by " . $this->calculate_percent_difference($start,$end) . " %";
        }
        else {
        	echo "Trend is <span style='color:green';>postive</span>.<br /> Up from six days ago by " . $this->calculate_percent_difference($start,$end) . " %";
        }
		
		
    }

    public function get_close_by_date($ticker_symbol,$date) {
    	$conn = $this->db_connection;
		$sql = "select * from stock_snapshot where ticker_symbol = '$ticker_symbol' and date_captured = '$date';";
		$result = mysqli_query($conn,$sql);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		return $row['close'];
    }

	public function display_chart_daterange($ticker_symbol,$date_begin,$date_end) {
		//declare a dates array to hold all the dates we will be looking at
		
		$date_begin_i = new DateTime($date_begin);
		$date_end_e = new DateTime($date_end);
		
		echo "<br /><div style='border:solid 1px black; width:200px;';>";
		echo 'Date Begin: ' . $date_begin . "<br />";
		echo 'Date End: ' . $date_end . "<br />";  
		echo "</div><br />";
	
		for($i=$date_begin_i;$i<=$date_end_e;$i->modify('+1 day')) {

			//First lets check to make sure the day we are attempting to pull the quotes
			//from is not a weekend day or a holiday (trading is closed on those days)
			if($this->check_weekend_and_holiday_dates($i)) { continue; }
			
			//We got a valid day. Now we can check to see if we have the particular stock
			//information in our database already. If not this function will attempt
			//to use yahoo stock api with simple csv to fill in the gap. 
			$this->check_snapshot($ticker_symbol,$i->format('Y-m-d'));
			
			//If we get to this step it means the data was succesfully captured into our database. Now we can use
			//our generic pull function to display the high, low, open and close for the particular day we are investigating
			$this->display_hloc_daily($ticker_symbol, $i->format('Y-m-d'));
			
		}
		
		}
	public function check_weekend_and_holiday_dates($date) {
		$day_of_week = date('l',strtotime($date->format('Y-m-d')));
		if($day_of_week == 'Sunday' || $day_of_week == 'Saturday') {
			//echo 'Weekend Date Detected/Not Bothering to check';
			return true;
		} else {
			return false;
		}

	}

		
	}
//end of analyzer class







