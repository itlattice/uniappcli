<?php
namespace process;

class Process extends Base
{
    public function run(){
        echo "Process is starting...\n";
        $this->openCli();
        $this->login();

        $cmd=CLI_PATH." user info";
        $line=exec($cmd,$output,$errorCode);
        if($errorCode!==0){
            $error=implode("\n",$output);
            echo "ERROR: $error\n";
            die("Failed to get user info: $line\n");
        }
        if($line=='0:user info:OK'){
            echo "Already logged in.\n";
        } else{
            $this->login();
        }

        $this->openProject();
        $packType=$this->projectConfig['pack'];
        foreach($packType as $type){
            switch($type){
                case 'app-android':
                case 'app-ios':
                    $buildConfig=$this->reConfig();  //每次打包前重新处理配置文件，确保修改生效
                    $this->buildApp($buildConfig);
                    break;
                
                default:
                    die("Unsupported pack type: $type\n");
            }
        }
    }

    private function reConfig(){
        $config=$this->projectConfigFile;
        $configData=file_get_contents($config);
        $configData=str_replace('{$path}', $this->projectPath, $configData);
        $projectConfig=jsonDecode($configData,true);

        //处理manifest.json中的{$path}占位符
        $manifestPath=$this->projectPath.'/manifest.json';
        if(!file_exists($manifestPath)){
            die("Error: manifest.json not found in project root.\n");
        }
        $manifestData=file_get_contents($manifestPath);
        $manifestData=str_replace('{$path}', $this->projectPath, $manifestData);

        if(isset($projectConfig['manifest'])){  //需要替换配置
            $manifestConfig=jsonDecode($manifestData,true);
            $manifestConfig=$this->arrayMergeRecursive($manifestConfig,$projectConfig['manifest']);
            $manifestData=json_encode($manifestConfig,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            $manifestData=str_replace('{$path}', $this->projectPath, $manifestData);
            $manifestData=str_replace('\/', '/', $manifestData);  //修正路径分隔符被转义的问题
            file_put_contents($manifestPath, $manifestData);
        }
        //处理打包配置
        $packConfigPath=$this->projectPath.'/'. $this->projectConfig['packConfigPath'];
        if(!file_exists($packConfigPath)){
            die("Error: pack config file not found at path: $packConfigPath\n");
        }
        $packConfig=file_get_contents($packConfigPath);
        $packConfig=str_replace('{$path}', $this->projectPath, $packConfig);
        
        $keystorePath=__DIR__."/../keystore/".$this->projectConfig['android']['keystorePath'];
        $keystorePath=str_replace('{$path}', $this->projectPath, $keystorePath);
        $keystorePath=realpath($keystorePath);
        $packConfig=str_replace('{$keystorePath}', $keystorePath, $packConfig);
        $pwdFile=str_replace('.keystore', '.pwd', $keystorePath);
        if(!file_exists($pwdFile)){
            die("Error: keystore password file not found at path: $pwdFile\n");
        }
        $keystorePassword=trim(file_get_contents($pwdFile));
        $packConfig=str_replace('{$keystorePassword}', $keystorePassword, $packConfig);

        $path=$this->projectPath."/dist";
        if(!is_dir($path)){
            mkdir($path,0777,true);
        }
        $packConfigFile=$path."/". uniqid().".json";
        file_put_contents($packConfigFile, $packConfig);

        return $packConfigFile;
    }

    private function arrayMergeRecursive($array1, $array2) {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->arrayMergeRecursive($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    protected function buildApp(string $buildConfig){
        echo "Building project...\n";
        $cmd=CLI_PATH." pack --config ". $buildConfig;
        $error='';
        $content="";
        foreach (runCommand($cmd, $error) as $line) {
            echo $line;
            $content.=$line."\n";
            $info=explode(' ', $line);
            if(count($info)<2){
                continue;
            }
            $info=$info[1];
            if(str_starts_with($info,'预编译器错误：')){
                die("Build failed with error: $line\n");
            }
            if(str_starts_with($info,'打包失败：')||str_contains($info,'Pack End')){
                die("Build failed with error: $line\n");
            }
        }
        echo "Project built successfully.\n";
    }

    protected function openProject(){
        echo "Opening project...\n";
        $cmd=CLI_PATH." project open --path ".$this->projectPath;
        $line=exec($cmd,$output,$errorCode);
        if($errorCode!==0){
            $error=implode("\n",$output);
            echo "ERROR: $error\n";
            die("Failed to open Project: $line\n");
        }
        echo "HBuilderX Project is Open.\n";
    }


    private function openCli(){
        echo "Opening HBuilderX CLI...\n";
        $cmd=CLI_PATH." open";
        $line=exec($cmd,$output,$errorCode);
        if($errorCode!==0){
            $error=implode("\n",$output);
            echo "ERROR: $error\n";
            die("Failed to open HBuilderX CLI: $line\n");
        }
        sleep(5);  //打开后等待一段时间，确保CLI完全启动
        echo "HBuilderX CLI is ready.\n";
    }
    
    private function login(){
        echo "Logging in...\n";
        $cmd=CLI_PATH." user login --username ".$this->config['USERNAME']." --password ".$this->config['PASSWORD'];
        $line=exec($cmd,$output,$errorCode);
        if($errorCode!==0){
            $error=implode("\n",$output);
            echo "ERROR: $error\n";
            die("Failed to login: $line\n");
        }
        sleep(10);  //登录后等待一段时间，确保会话建立完成
        echo "Login successful.\n";
        return true;
    }
}