<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 2016/10/20
 * Time: 10:19
 */
$config["texture"] = array(
    1 => array("humidity"=>array("石质","陶器","瓷器")),
    2 => array("humidity"=>array("铁质","青铜")),
    3 => array("humidity"=>array("纸质","壁画","纺织品","漆木器")),
    4 => array("light"=>array("石质","陶器","瓷器","铁质","青铜")),
    5 => array("light"=>array("纸质","壁画","纺织品")),
    6 => array("light"=>array("漆木器")),
    7 => array("temperature"=>array()),
    8 => array("ul"=>array()), //紫外
    9 => array("voc"=>array())
);

$config['humidity'] = array(
    1=>array("石质","陶器","瓷器"),
    2=>array("铁质","青铜"),
    3=>array("纸质","壁画","纺织品","漆木器","其他")
);

$config['light'] = array(
    1=>array("石质","陶器","瓷器","铁质","青铜"),
    2=>array("纸质","壁画","纺织品"),
    3=>array("漆木器","其他")
);