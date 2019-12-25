# bilibiliAutoCoinExp
Bilibili 每日自动投 5 个硬币，获取 50 经验。  
仅有 1 个 PHP 文件，只给 Bilibili 的番剧投币，防止被认为是黑产账号。  
支持 [Server酱](http://sc.ftqq.com/3.version) 推送运行结果到微信。

#### 使用说明
修改 run.php 的 $cookie , $crsf , $server_chan_key 为自己的。  
Bilibili 的 $cookie , $crsf 可以通过浏览器开发者工具获得，  
$server_chan_key 为 Server 酱的 key ，可以不填。

#### 获取cookie和跨站crsf
Chrome 打开任意一个 Bilibili 的视频播放界面，按下 F12 打开开发者工具，  
切换到 Network 选项卡，然后给视频投 1 个硬币。  
投币后，查看 Network 的 add ，复制即可。
![bilibili_auto_coin_1](https://iobaka.com/cloud/image/bilibili_coin.png)

#### 运行效果
![bilibili_auto_coin_2](https://iobaka.com/cloud/image/bilibili_coin_2.png)

#### 添加到计划任务
添加到 linux 计划任务 Crontab 中，每日 0 点 10 分自动执行。执行结果写入 run.log 文件中。
```
10 0 * * * php /opt/bilibili_auto_coin/run.php>/opt/bilibili_auto_coin/run.log
```
路径地址记得改成你自己的。
