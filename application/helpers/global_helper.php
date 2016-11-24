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
		if(is_cli()){
			echo iconv('utf-8', 'GBK', $msg).'...'.date('H:i:s').PHP_EOL;
		}else{
			echo $msg.'...'.date('H:i:s').'<br>';
		}
		
		// 日志
		$f = fopen('logs'.date('Ym').'.log', 'a');
		fwrite($f, $msg.'...'.date('H:i:s').PHP_EOL);
		fclose($f);

		if($e) exit;
	}
}



