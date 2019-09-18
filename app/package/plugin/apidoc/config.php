<?php
return [
    'title' => "APi接口文档",  //文档title
   	'version'=>'1.0.0', //文档版本
    'copyright'=>'Powered By Wangtao', //版权信息
    'controller' => [
    		//需要生成文档的类
    		"DemoAction",
    		"DocAction",
    		"WeiXinAction"
    ],
	'controller_path' => LIB_PATH.'Action/Home/',//需要生成文档的类路径（将加载此路径下所有类，同时也会加载controller设置的类）
	'filter_controller' => [
			//过滤 不解析的类名称
			'AuthAction',
			'AliPayAction',
			'SMSAction'
	],
	'filter_method' => [
   		//过滤 不解析的方法名称
   		'_empty',
   		'__isset',
   		'__call',
   		"__destruct",
   		'get'
  	],
    'return_format' => [
    		//数据格式
    		'status' => "200/300/301/302",
    		'message' => "提示信息",
  	]
];
?>