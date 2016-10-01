<?php
//本demo用于简单的步骤细节化演示,没有做代码和方法的抽取,异常直接抛出元数据//

$lpath="/root/test.jpg";		//上传文件的绝对路径
$upath="/test.jpg";		//存储到CDN的路径
$qzone="test";	//服务名称
$user="user";			//操作员账号
$passwd="passwd";	//操作员密码

//---------------上面为需要填写的个人信息,下面内容无需改动---------------//

$i=0;$k=0;$bsize=1048576;
$length=filesize($lpath);
$method="PUT";
$accpoint="http://v0.api.upyun.com/";

//初始化上传
$date = gmdate('D, d M Y H:i:s \G\M\T');
$sign=md5($method."&/".$qzone.$upath."&".$date."&".@strlen($body)."&".md5($passwd));
$stra="UpYun ".$user.":".$sign;

$_headers=array();
array_push($_headers, "Content-Length: ".@strlen($body));
array_push($_headers, "Authorization: ".$stra);
array_push($_headers, "Date: ".$date);
array_push($_headers, "X-Upyun-Multi-Stage: initiate");
array_push($_headers, "X-Upyun-Multi-Length: ".$length);

$ch = curl_init($accpoint.$qzone.$upath);
curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_POST, 1);

$response=curl_exec($ch);
$res_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
$temp=explode(":",$response);
$muuid=explode("\n",$temp[12]);
if($res_code==204){
	echo "\nInitialization succeed,start uploading.\n";
}else{
	echo "\nInitialization failed.\n".$response;
	exit();
}
curl_close($ch);


//开始上传
$fh=fopen($lpath,'rb');
while(!feof($fh)){
	$handle = fopen("tempfile.{$i}","wb");  
	fwrite($handle,fread($fh,$bsize)); 
	fclose($handle);
	$handle = fopen("tempfile.{$i}","rb"); 

	$sign=md5($method."&/".$qzone.$upath."&".$date."&".filesize("tempfile.{$i}")."&".md5($passwd));
	$stra="UpYun ".$user.":".$sign;

	$ch = curl_init($accpoint.$qzone.$upath);
	fseek($handle, 0, SEEK_END);
	$length = ftell($handle);
	fseek($handle, 0);

	curl_setopt($ch, CURLOPT_INFILE, $handle);
	curl_setopt($ch, CURLOPT_INFILESIZE, $length);

	$_headers=array();
	array_push($_headers, "Content-Length: ".$length);
	array_push($_headers, "Authorization: ".$stra);
	array_push($_headers, "Date: ".$date);
	array_push($_headers, "X-Upyun-Multi-Stage: upload");
	array_push($_headers, "X-Upyun-Multi-UUID: ".$muuid[0]);
	array_push($_headers, "X-Upyun-Part-ID: ".$k++);

	curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_POST, 1);

	$response=curl_exec($ch);
	$res_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if($res_code==204){
		echo "block ".($k-1)." upload success,continue...\n";
	}else{
		echo "block ".($k-1)." upload failed,stop.";
		echo $response;
		exit();
	}

	curl_close($ch);
	fclose($handle);
	unlink("tempfile.{$i}");
	$i++;
}
fclose ($fh);


//完成上传
$date = gmdate('D, d M Y H:i:s \G\M\T');
$sign=md5($method."&/".$qzone.$upath."&".$date."&".@strlen($body)."&".md5($passwd));
$stra="UpYun ".$user.":".$sign;

$_headers=array();
array_push($_headers, "Content-Length: ".@strlen($body));
array_push($_headers, "Authorization: ".$stra);
array_push($_headers, "Date: ".$date);
array_push($_headers, "X-Upyun-Multi-Stage: complete");
array_push($_headers, "X-Upyun-Multi-UUID: ".$muuid[0]);

$ch = curl_init($accpoint.$qzone.$upath);
curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_POST, 1);

$response=curl_exec($ch);
$res_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
if($res_code==201 || $res_code==204){
	echo "Upload finished.\n";
}else{
	echo "Upload failed.\n".$response;
}
curl_close($ch);



?>