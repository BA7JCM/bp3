<?php
// 文件管理
    session_start();
    $user = $_SESSION['user'];
    if(!$user){
        echo '您还未登陆。';
        die;  // 终止后续解析
    }
    require('../config.php');
    require('../functions.php');
    // 获取当前路径
    $page_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
    $page_url = str_replace("/admin/file.php","",$page_url);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>文件管理 | bp3</title>
    <link href="../favicon.ico" rel="shortcut icon" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <link href="../fonts/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
</head>
<body style="background-color:rgb(231,231,231);">
 
    <header >
    <nav class="navbar navbar-default">
      <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="./">管理系统</a>
        </div>
    
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
         <ul class="nav navbar-nav">
          </ul>
          <ul class="nav navbar-nav">
            <li class="active"><a href="./file.php">文件管理<i class="fa fa-th-large" aria-hidden="true"></i><span class="sr-only">(current)</span></a></li>
            <li><a href="./settings.php">修改设置<i class="fa fa-cog"></i></a></li>
            <li><a href="./help.php">帮助与支持<i class="fa fa-question-circle" aria-hidden="true"></i></a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="../">前台<i class="fa fa-home"></i></a></li>
            <li><a href="./logout.php">注销<i class="fa fa-sign-out" aria-hidden="true"></i></i></a></li>
          </ul>
        </div><!-- /.navbar-collapse -->
      </div><!-- /.container-fluid -->
    </nav>
  
    </div><div id="myheader" class="container-fluid">
        <div class="container jumbotron">
            <div class="row">
                <div class="col-xs-12">
                    <ol class="breadcrumb">
 <?php
    // 捕获dir查询参数
    $dir = $_GET['dir'];
    echo "<li><a href='./file.php'>Home</a></li>";
    if(!empty($dir)){ // 非根目录，一个或多个
        $dir = urldecode($dir);
        $dirs = explode('/',$dir); // 取得路径
        $dir_path = '';  // 新的访问路径
        $dir_paths = [null,]; // 存储新路径组
        for($i=1;$i<count($dirs);$i++){
            $dir_path.='/';
            $dir_path.=$dirs[$i];
            $dir_paths[$i] = $dir_path;
            $dir_link = urlencode($dir_path);
            echo "<li><a href='?dir=$dir_link'>$dirs[$i]</a></li>";
        }
    }
?>
                    </ol>
                </div>
            </div>
            <form>
                <div class="row">
                    <div class="col-xs-12">
                    <div class="search-wraper" role="search">
                        <div class="input-group">
                          <input type="text" name="s" class="form-control" placeholder="<?php 
                          if($_GET['s'])
                            echo '正在搜索：'.$_GET['s'];
                            else echo '搜索文件';?>" required="required">
                          <span class="input-group-btn">
                            <button class="btn btn-default" type="submit">Go!</button>
                          </span>
                        </div><!-- /input-group -->
                    </div>
                    </div><!--col-xs-12-->
              </div><!--row-->
            </form>
            </div>
        </div>
    </header>
    
<main>
    <div class="container">
<div class="bs-example" data-example-id="hoverable-table">
    <table class="table table-bordered table-responsive">
<?php
    // 获取查询方式，调用查询方法，优先query，参数s
    $predir = '';
    $access_token = $config['identify']['access_token'];
    $has_more = false; // 全局共享变量，是否有下一页
    $page;  // 当前页数
    if($_GET['s']){ // 取得query参数
        $key = $_GET['s'];
        $page = $_GET['page']; // 捕获分页参数
        if(empty($page)){$page=1;}
        $url = "http://pan.baidu.com/rest/2.0/xpan/file?dir=$predir&access_token=$access_token&web=1&recursion=1&page=$page&num=20&method=search&key=$key";
        $opts = array(
            'http' => array(
                'method' => 'GET', 
                'header' => 'USER-AGENT: pan.baidu.com'
                ));
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        $json = json_decode($result);
        $has_more = $json->has_more;
        if(!$json->list){
            echo("这儿似乎什么也没有...");
        }else{
            echo "<thead class='active'><tr><th></th><th>文件<i class='glyphicon glyphicon-chevron-down'></i></th><th>大小<i class='glyphicon glyphicon-chevron-down'></i></th><th>下载<i class='glyphicon glyphicon-chevron-down'></i></th></tr></thead><tbody>";
            // var_dump($json);
            foreach ($json->list as $row){
                if($row->isdir==1){
                    // 去掉前缀
                    $path = substr($row->path,strlen($predir));
                    $encode_path = urlencode($path);
                 echo "<tr><th scope='row'><i class='glyphicon glyphicon-folder-open'></i></th><td class='info' colspan='3' ><a href='?dir=$encode_path' style='display:block'>$path</a></td></tr>";
                 }else{
                     $fsid = $row->fs_id;
                     $show_size = height_show_size($row->size);
                 echo "<tr><th scope='row'><i class='glyphicon glyphicon-file'></i></th><td>$row->server_filename</td><td>$show_size</td>
          <td>
              <div class='btn-group' role='group' aria-label='...'>
              <a href='$page_url/dn.php?fsid=$fsid' type='button' class='btn btn-default'>下载</a>
              <button type='button' class='btn btn-default cp' data-clipboard-text='$page_url/dn.php?fsid=$fsid'>复制</button>
              </div>
          </td>
        </tr>";
                }
            }
            echo "</tbody>";
        }
    }
    else{ // dir查询
        $dir = $_GET['dir'];
        if(!$dir){ // 根目录
            $dir = $predir;
            if($predir==""){
                $dir = "/";
            }
        }else{
            $dir = $predir.$dir;
        }
        $dir = urlencode($dir);
        $url = "https://pan.baidu.com/rest/2.0/xpan/file?method=list&dir=$dir&order=name&start=0&limit=100&web=web&folder=0&access_token=$access_token&desc=0";
        $opts = array(
            'http' => array(
                'method' => 'GET', 
                'header' => 'USER-AGENT: pan.baidu.com'
                ));
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        // var_dump($result);
        $json = json_decode($result);
        if(!$json->list){
            echo("这儿似乎什么也没有...");
        }else{
            echo "<thead><tr class='active'><th></th><th>文件<i class='glyphicon glyphicon-chevron-down'></i></th><th>大小<i class='glyphicon glyphicon-chevron-down'></i></th><th>下载<i class='glyphicon glyphicon-chevron-down'></i></th></tr></thead><tbody>";
            // var_dump($json);
            foreach ($json->list as $row){
                if($row->isdir==1){
                    // 去掉前缀
                    $path = substr($row->path,strlen($predir));
                    $encode_path = urlencode($path);
                 echo "<tr><th scope='row'><i class='glyphicon glyphicon-folder-open'></i></th><td class='info' colspan='3' ><a href='?dir=$encode_path' style='display:block'>$path</a></td></tr>";
                 }else{
                     $fsid = $row->fs_id;
                     $show_size = height_show_size($row->size);
                 echo "<tr><th scope='row'><i class='glyphicon glyphicon-file'></i></th><td class='br'>$row->server_filename</td><td>$show_size</td>
          <td>
              <div class='btn-group' role='group' aria-label='...'>
              <a type='button' class='btn btn-default' href='$page_url/dn.php?fsid=$fsid'>下载</a>
              <button type='button' class='btn btn-default cp' data-clipboard-text='$page_url/dn.php?fsid=$fsid'>复制</button>
              </div>
          </td>
        </tr>";
                }
            }
            echo "</tbody>";
        }
    }
?>
    </table>
  </div>
    </div>

<div class="container">
    
<?php  // 查询时存在分页
    if($_GET['s'] and $has_more){
        $s = $_GET['s'];
        $prev_page = $page-1;
        $next_page = $page+1;
    echo "<ul class='pager'>";
    if($page>1){
        echo "<li><a href='./file.php?page=$prev_page&s=$s'>上一页</a></li>";
    }
    echo "<li><a>分页$page</a></li>";
    echo "<li><a href='./file.php?page=$next_page&s=$s'>下一页</a></li>";
    echo "</ul>";
    }
?>
    
</div>
</main>
<footer class="navbar navbar-default navbar-fixed-bottom navbar-inverse copyright">
        <p class="text-center" style="color:#9d9d9d;margin-top:15px;">Copyright © bp3 <?php echo date('Y')?></p>
</footer>
<a href="javascript:(function(){window.scrollTo(0, 0);})();" title="返回顶部" id="back-to-top" style="display:none;position:fixed;right:10px;bottom:10px;background-color:rgb(95,99,104);box-sizing: border-box;cursor:pointer;text-align:center;"><i class="fa fa-angle-up" style="height:40px;width:40px;display:iniline—block;line-height:40px;color:#fff;"></i></a>
<style>
    .copyright{
        margin-bottom: 0px;
    }
</style>
<script src="../js/clipboard.min.js"></script>
<script>
    $(function () {
      if($(window).height()==$(document).height()){
        $(".copyright").addClass("navbar-fixed-bottom");
      }
      else{
        $(".copyright").removeClass(" navbar-fixed-bottom");
      }    
    });
    var btns = document.querySelectorAll('.cp');
    var clipboard = new ClipboardJS(btns);
    clipboard.on('success', function(e) {
        alert("复制成功");
    });
    clipboard.on('error', function(e) {
        alert("复制失败");
    });
    $(window).scroll(function(){
        let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        if(scrollTop>50){
            $("#back-to-top").css("display","block");
        }else{
            $("#back-to-top").css("display","none");
        }
    });
</script>
</body>
</html>