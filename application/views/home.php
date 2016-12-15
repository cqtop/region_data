<!DOCTYPE html>
<html>
<head>
	<title>统计</title>
	<link rel="stylesheet" type="text/css" href="./public/datepicker/jquery-ui.min.css">
	<script type="text/javascript" src="./public/jquery-1.10.2.min.js"></script>
	<script type="text/javascript" src="./public/datepicker/jquery-ui.min.js"></script>
</head>
<body>

统计日志:
<select>
	<?php foreach($month as $m):?>
	<option value="<?php echo $m?>"><?php echo $m?></option>
	<?php endforeach;?>
</select>

博物馆数据统计:
<input type="text" id="datepicker" placeholder="选择日期">

<hr>
<pre></pre>

<script type="text/javascript">

	$("select").change(function(){
		$.post('./', {month: $(this).val()}, function(data){
			$("pre").html(data);
		});
	}).change();

	$( "#datepicker").datepicker({
		dateFormat: 'yy-mm-dd',
		maxDate: '0D'
	}).on("change", function() {
		$.get('./home/countmusem/'+$(this).val(), function(data){
			$("pre").html(data);
		});
    });
</script>
</body>
</html>