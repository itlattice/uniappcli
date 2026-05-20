<?php
namespace process;

class Base
{
    /**
     * 项目配置数组
     */
    protected ?array $projectConfig;
    /**
     * 项目路径
     */
    protected string $projectPath;
    /**
     * 项目配置文件路径
     */
    protected string $projectConfigFile;

    /**
     * Base constructor.
     * @param array $config 配置数组
     */
    public function __construct(
        protected array $config
    )
    {
        define('CLI_PATH', $this->config['CLI_PATH'] ?? '');
        $this->projectPath=getcwd();
        $this->projectConfigFile=$this->projectPath.'/project.config.json';
        if(!file_exists($this->projectConfigFile)){
            die("Error: project.config.json not found in project root.\n");
        }
        $this->projectConfig=jsonDecode(file_get_contents($this->projectConfigFile),true);
        if($this->projectConfig===null){
            die("Error: Failed to parse project.config.json.\n");
        }
    }
}
