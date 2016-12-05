<?php
/**
* 调试函数
*/
if(! function_exists("debug")){
	function debug(){
		foreach (func_get_args() as $k => $arg) {
			echo '<h2>========= #'.($k+1).' =========</h2>';
			echo '<pre>';
			print_r($arg);
			echo '</pre>';
		}
		exit;
	}
}


/**
* 调试函数
*/
if(! function_exists("lineMsg")){
	function lineMsg($msg, $e=FALSE){
		$msg .= '...'.date('H:i:s').PHP_EOL;
		
		// 日志
		$f = fopen('logs'.date('Ym').'.log', 'a');
		fwrite($f, $msg);
		fclose($f);

		echo $msg.(is_cli()?'':'<br>');
		if($e) exit;
	}
}



