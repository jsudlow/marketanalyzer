<html>
<head>
	<title>Abbs Stock Analyzer</title>
</head>
<body>
<h1> Abbs Stock Analyzer</h1>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	Ticker Symbol: <input type='text' name="ticker_name" /><br />
	<input type='submit' name='submit' value='Analyze Stock' />
</form>
</body>
</html>
<?php
	include('core_classes.php');

	$server = new ServerProperties;


	//If the page is in post mode (a ticker symbol was entered)
	if ($server->is_post()) {
		//If a ticker symbol was entered correctly
		$post = new PostAccess;
		if ($post->check_ticker()) {
			//run analyze on the given ticker symbol
			$analyzer = new analyzer;
			$analyzer->analyze($post->get_ticker_symbol());
		}
		else {
			echo 'Ticker not entered';
		}
	}
	?>
