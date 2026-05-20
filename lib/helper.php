<?php
use ColinODell\Json5\Json5Decoder;

if(!function_exists('env')){
    /**
     * 获取环境变量
     * @param string $key 环境变量的键
     * @param mixed $default 当环境变量不存在时返回的默认值，默认为
     * @return mixed 环境变量的值，如果环境变量不存在则返回默认值
     */
    function env($key,$default=null){
        $value=getenv($key);
        if($value===false){
            return $default;
        }
        return $value;
    }
}

if(!function_exists('jsonDecode')){
    /**
     * 解析json5字符串
     * @param string $json json5字符串
     * @param bool $assoc 是否返回关联数组，默认为true
     * @return mixed 解析后的数据
     */
    function jsonDecode($json,$assoc=true){
        $data = Json5Decoder::decode($json, $assoc);
        return $data;
    }
}

if(!function_exists('runCommand')){
    /**
     * 执行系统命令，使用 yield 实时输出 stdout + stderr
     * @param string $cmd 要执行的命令
     * @param string $error 执行失败的错误信息（引用返回）
     * @return \Generator 逐行输出内容
     */
    function runCommand(string $cmd, string &$error = ''): \Generator
    {
        $error = '';

        // 定义管道：stdout + stderr 都读取
        $descriptors = [
            0 => ['pipe', 'r'],  // 标准输入（这里不需要，保留即可）
            1 => ['pipe', 'w'],  // 标准输出
            2 => ['pipe', 'w'],  // 标准错误
        ];

        // 打开进程
        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            $error = "进程创建失败 proc_open()";
            return false;
        }

        // 将管道设置为非阻塞，实现同时读取 stdout/stderr
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $errorOutput = '';

        // 循环监听两个管道，直到进程结束
        while (true) {
            // 读取 stdout
            $stdoutLine = fgets($pipes[1]);
            if ($stdoutLine !== false) {
                $output .= $stdoutLine;
                yield $stdoutLine; // 实时输出
            }

            // 读取 stderr
            $stderrLine = fgets($pipes[2]);
            if ($stderrLine !== false) {
                $errorOutput .= $stderrLine;
                yield $stderrLine; // 错误也实时输出
            }

            // 检查进程是否结束
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            // 小睡避免 CPU 空转
            usleep(10000); // 0.01 秒
        }

        // 关闭管道
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        // 获取进程退出码
        $exitCode = proc_close($process);

        // 赋值错误信息
        $error = trim($errorOutput);

        // 退出码 0 表示成功
        return $exitCode === 0;
    }
}