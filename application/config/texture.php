<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 2016/10/20
 * Time: 10:19
 */
$config["texture"] = array(
    "zgkf" => array( //展柜 库房
        1 => array("humidity"=>array("石质","陶器","瓷器")),
        2 => array("humidity"=>array("铁质","青铜")),
        3 => array("humidity"=>array("纸质","壁画","纺织品","漆木器")),
        4 => array("light"=>array("石质","陶器","瓷器","铁质","青铜")),
        5 => array("light"=>array("纸质","壁画","纺织品")),
        6 => array("light"=>array("漆木器"))
    ),
    "hh" => array(//混合材质
        12 =>array("humidity"=>array("混合材质")),
        13 =>array("light"=>array("混合材质"))
    ),
    "common" => array( //共有
        7 => array("temperature"=>array()),
        8 => array("uv"=>array()), //紫外
        9 => array("voc"=>array()),
    ),
    "zt" => array( //展厅
        10 => array("humidity"=>array()), //展厅不分材质
        11 => array("light"=>array())
    )
);
