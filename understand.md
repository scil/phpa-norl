phpa-norl  
phpa的no readline的改造版

结构
===

```

注册退出函数register_shutdown_function : 输出错误信息
入口：验证php版本、输出环境信息
打开标准输入 fopen('php://stdin','rb')
for(;;)

得到一句输入 fgets($fh,1024);   
     1024对于命令行输入来说足够大了；
     可接受多行输入(行末用#标识)
     可提供提示功能(行末用\t标识)
处理输入
     命令则执行
     非命令：存入历史数组，eval，并用ob获得eval中可能的输出
```

功能亮点
==

windows 不支持readline,如何模拟其功能的呢？
----

```
     读取用户在console上的输入？
          $fh=fopen('php://stdin','rb')
          fgets($fh,1024);   //证明一个命令最多1024长，除非使用多行写法:# ; myReadLine中的循环就是为支持多行的
          //也可使用预定义常量STDIN 如:$data=trim(fread(STDIN, 1024));
     历史
          $__phpa_myhist
     用户输入的代码是如何执行的？
          $__phpa_ret = eval("unset(\$__phpa_line); $__phpa_line;");  // eval运行于脚本的全局范围内，不是包裹在函数里，保证了变量的全局性



```


hint
---
```
提示变量、常量、还是类...?
     $ 应该是变量或对象
     ABC 倾向是常量
     ....



```

?
===

```
__phpa__is_immediate 是否需要加return
清理代码
     ? 单引号或双引号内遇到\, $i++ 即跳过字符串里符号\后面的一个字符
     清理所有比较运算符，用str_replace
包含;{=的，false
     用了strcspn
包含如echo class等关键词，false
     用了preg_split("/[^A-Za-z0-9_]/", $code);


eval的返回值
如果是字符串类型，输出时：addcslashes($__phpa_ret, "\0..\37\177..\377")


上一位修改者的代码
$line = rtrim($line, ';'); // strip last ";"




```
