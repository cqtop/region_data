<!DOCTYPE html>
<html>
<head>
	<title>统计</title>
	<script type="text/javascript" src="./public/jquery-1.10.2.min.js"></script>
</head>
<body>
统计日志：
<select>
	<?php foreach($month as $m):?>
	<option value="<?php echo $m?>"><?php echo $m?></option>
	<?php endforeach;?>
</select>
<input type="button" name="input_museum" value="博物馆统计">
<hr>
<pre></pre>

<script type="text/javascript">

	$("select").change(function(){
		$.post('./', {month: $(this).val()}, function(data){
			$("pre").html(data);
		});
	});

	$("[name=input_museum]").click(function(){
		$.post('./home/countmusem', function(data){
			$("pre").html(data);
		});
	});
</script>
</body>
</html>