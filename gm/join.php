<?php
$user=$_POST["user"];
if($user==""){
echo "无效";
}else{
  if(is_dir("../adminer/$user")){
    echo "dlcg";
  }else{
    echo "无效";
  }
}
?>