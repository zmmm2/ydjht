<?php
session_start();
if(!is_dir('file/'.$_GET['user'])){
    mkdir('file/'.$_GET['user'],0777,true);//初始化
}

$filename= substr(strrchr($_FILES['file']['name'], '.'), 1);
$_FILES['file']['name']= str_replace('&','_',$_FILES['file']['name']);
$_FILES['file']['name']= str_replace('#','_',$_FILES['file']['name']);
if (!preg_match('/[\x7f-\xff]/', $_FILES['file']['name'])){
    if($_FILES['file']['name']!=''){
        $maxsize=10;//单次上传最大容量，单位:M
        $usermaxsize=50;//一个用户最大容量，单位:M
        $maxsize=$maxsize*1024*1024;
        $usermaxsize=$usermaxsize*1024*1024;
        
        if($_FILES['file']['size']<$usermaxsize){
            if($_FILES['file']['size']>$maxsize){
                echo '<center>单次上传不能大于10M</center>';
            }else{
                if($filename=='apk'||$filename=='txt'||$filename=='gif'||$filename=='jpg'||$filename=='jpag'||$filename=='png'||$filename=='zip'||$filename=='doc'||$filename=='docx'||$filename=='exe'||$filename=='ppt'||$filename=='iApp'||$filename=='rar'){
                    if(strpos($filename,'[')===false&&strpos($filename,'|')===false&&strpos($filename,']')===false){
                        move_uploaded_file($_FILES['file']['tmp_name'],'file/'.$_GET['user'].'/'.$_FILES['file']['name']);
                        echo '<center><br /><br />上传成功<content>';
                        echo '<br><center>文件名:'.$_FILES['file']['name'].'<content>';
                        echo '<br><center>文件类型:'. $_FILES['file']['type'].'<content>';
                    }else{echo '<center>文件名错误</center>';}
                }else{echo '<center>文件格式错误</center>';}
            }
        }else{echo '<center>上传失败:文件太大</center>';}
    }else{echo '<center>请选择文件</center>';}
}else{echo '<center>文件名不可包含中文</center>';}
session_destroy();
?>