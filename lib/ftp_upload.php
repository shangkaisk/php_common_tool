<?php
/**
 * 之前的cmd ftp上传报错，用php进行了重写
 * @since 2018.04.28
 * @author shangkai
 */


/****
 *
  使用帮助
//ip端口
$config['host'] = '';
$config['port']  = '';

//用户名密码
$config['user'] = '';
$config['password'] = '';

//本地待上传文件路径
$config['local_path'] = 'c:\ftp\\';

$config['ftp_path'] = '/db';

//执行ftp上传功能
$ftp = new  ftp_upload($config);
$ftp->start();
 *
 *
**/
//---------------------功能代码--------------------------------------------------------

class ftp_upload
{
    private $host ;
    private $port ;
    private $user ;
    private $password ;
    private  $local_path ;
    private  $ftp_path ;
    private  $file_pattern = '*.rar';

    public  function  __construct($config)
    {
        $this->init($config);
    }
    public  function  init ($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->local_path = $config['local_path'];
        $this->ftp_path = $config['ftp_path'];

    }

    /**
     * 上传功能调用入口
     */
    public  function  start()
    {
        $a_upload_file = $this->find_all_file();
        foreach ($a_upload_file as $v_upload_file)
        {
            $this->upload_operation($v_upload_file);
        }
    }

    /**
     * 获取所有符合上传规则的文件名，返回给调用者
     * @return array
     */
    private  function  find_all_file ()
    {
        return glob( $this->local_path.$this->file_pattern );
    }
    /**
     * ftp上传文件功能
     * @param $upload_file 待上传的文件
     */
    private function  upload_operation ($upload_file)
    {
        // 进行ftp登录，使用给定的ftp登录用户名和密码进行login
        $f_conn = ftp_connect($this->host, $this->port);
        $f_login = ftp_login($f_conn,$this->user,$this->password);
        if(!$f_login){
            echo "login fail\n";
            return ;
        }


        // 获取当前所在的ftp目录
        $in_dir = ftp_pwd($f_conn);
        if(!$in_dir){
            echo "get dir info fail\n";
            return;
        }


        //被动模式
        ftp_pasv($f_conn, true);
        //必须放在被动模式之后，否则无法获取目录
        $this->change_and_create_dir($f_conn);
        //上传
        $result = ftp_put($f_conn, basename($upload_file), $upload_file, FTP_BINARY);
        if(!$result){
            echo "upload file fail\n";
        }else{
            echo "upload file success\n";

        }
        ftp_close($f_conn);
    }

    /**
     * 定位之文件上传目录 如果不存在则创建
     * @param $ftp_connection ftp变量
     */
    private  function  change_and_create_dir ($ftp_connection)
    {
        $a_file = ftp_nlist($ftp_connection,'/');
        if(in_array($this->ftp_path,$a_file)===false)//没有目录则创建
        {
           ftp_mkdir($ftp_connection, $this->ftp_path);
        }
//         切换目录
        ftp_chdir($ftp_connection, $this->ftp_path);
    }

}

