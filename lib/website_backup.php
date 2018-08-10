<?php
/**
 * 使用场景：
 * $web_path目录下的所有子文件夹进行压缩、ftp上传至 备份服务器
 *
 * 备注：ftp上传依赖于 ftp_upload.php
 *
 * @author:shangkai
 * @since 2018.08.10
 */
$website_backup = new website_backup();
$website_backup->start();


class website_backup
{
   private $web_path = '/data/wwwroot';
    
    private $backup_path = '/tmp/website_backup' ;

    /**
     * $only_backup_website_path,$ignore_website_path 互斥，$only_backup_path优先生效
     */
    private $only_backup_website_path = array(  ) ;//仅备份定义的子目录
    private $ignore_website_path = array(  ) ;//定义的子目录会忽略，不备份

    private $ignore_website_file_mame = array( '*thumb_*.jpg','*thumb_*.png' );//忽略临时文件，例如：缩略图
    private $zip_password ='';//压缩密码
    private  $file_pattern = '*.zip';//备份前删除*.zip文件
    public  function  __construct()
    {
        set_time_limit(0);
        date_default_timezone_set("PRC");
    }
    /**
     * 开始 1.执行备份 2.并进行上传至备份服务器
    **
    */
    public  function start()
    {

        $this->create_dir_if_not_exists($this->backup_path);
        $this->replace_path_suffix_slash();

        $a_dir = $this->get_all_website_path();

        foreach ($a_dir as $v_dir)
        {
            $this->remove_all_expired_file();
            $this->do_zip($v_dir);
            $this->do_ftp();
        }

    }


    /**
     * 如果无指定路径则 递归创建
     * @param $path
     */
    public  function  create_dir_if_not_exists ($path)
    {
        if (!file_exists($path))
        {
            $this->create_dir_if_not_exists(dirname($path));
            mkdir($path);
        }
    }



    /**
     * 替换掉路径里面的 最后的 /
     */
    public function  replace_path_suffix_slash ()
    {
        if (substr($this->web_path,-1,1)=='/')
        {
            $this->web_path = substr($this->web_path,0, (strlen($this->web_path)-1) );
        }
        if (substr($this->backup_path,-1,1)=='/')
        {
            $this->backup_path = substr($this->backup_path,0, (strlen($this->backup_path)-1) );
        }

    }

    /**
     * 获取需要备份的文件夹。根据策略1.忽略特定目录，2.仅备份特定目录
     *
     * @return array
     */
    public  function  get_all_website_path ()
    {

        $all_website_path = array();
        $a_dir = glob($this->web_path.'/*',GLOB_ONLYDIR|GLOB_NOSORT);
        if ($this->only_backup_website_path)
        {
            foreach ($a_dir as $v_dir)
            {
                if(in_array( basename($v_dir), $this->only_backup_website_path ))
                {
                    $all_website_path [] = $v_dir;
                }
            }
        }
        else
        {
            foreach ($a_dir as $v_dir)
            {
                if(!$this->is_ignore_path($v_dir))
                {
                    $all_website_path [] = $v_dir;
                }
            }
        }
        return $all_website_path;
    }

    /**
     * 移出所有的zip文件，以免磁盘占用过多
     */
    public  function  remove_all_expired_file ()
    {
        $a_file = glob( $this->backup_path.'/'.$this->file_pattern );

        foreach ( $a_file as $v_file )
        {
            echo $v_file;
            unlink($v_file);

        }
    }


    /**
     * 执行压缩命令
     * @param $dir
     */
    public  function  do_zip ($dir)
    {
        $shell_cmd_zip= sprintf(
            'zip -r9P %s "%s" %s %s',
            $this->zip_password,
            $this->get_zip_full_path_name($dir),
            $dir
            ,$this->get_zip_ignore_file_name());

        shell_exec($shell_cmd_zip);
        echo $shell_cmd_zip;
    }

    /**
     * 执行ftp命令
     */

    public  function  do_ftp ()
    {
        $shell_cmd_ftp = sprintf('php %s/ftp_upload.php %s', dirname(__FILE__), ($this->backup_path.'/') );
        shell_exec($shell_cmd_ftp);
    }

    /**
     * 是否忽略此路径
     * @param $path
     * @return bool [true 忽略][false 不忽略]
     */
    public  function is_ignore_path ($path)
    {
        if ( in_array( basename($path), $this->ignore_website_path ) )
        {
            return true;
        }
        return false;
    }

    /**
     * 排除掉 $this->ignore_website_file_mame定义的文件
     */
    public function  get_zip_ignore_file_name ()
    {
        $zip_command = '';
        foreach ($this->ignore_website_file_mame as $v_file_name)
        {
            $zip_command .= sprintf(' -x %s',$v_file_name);
        }
        return $zip_command;

    }

    /**
     * 压缩文件的全名
     * @param $path
     * @return string
     */
    public function  get_zip_full_path_name ($path)
    {
        $file_name = sprintf('%s/%s_%s.zip',$this->backup_path,basename($path),date('Ymd-Hms') );
        return $file_name;
    }

}
