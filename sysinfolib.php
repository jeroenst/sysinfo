<?php

function getSystemMemInfo() 
{       
    $data = explode("\n", file_get_contents("/proc/meminfo"));
    $meminfo = array();
    foreach ($data as $line) {
        if ($line != '')
        {
    	  list($key, $val) = explode(":", $line);
    	  $meminfo[$key] = (int)(substr(trim($val),0,-3) / 1024);
        }
    }
    return $meminfo;
}

function getLoad()
{
  $load = array();
  $procload = explode (" ",  file_get_contents("/proc/loadavg"));
  $load['1min'] = $procload[0];
  $load['5min'] = $procload[1];
  $load['15min'] = $procload[2];
  return $load;
}

function getProcessInfo()
{
  $psinfo = array();
  $processinfo = array();
  exec ("ps axk -pcpu,-pmem,-size,pid  o pid,pcpu,pmem,size,comm,args", $psinfo);
  foreach ($psinfo as $line)
  {
      $psfields = preg_split("#\s+#", trim($line));
      $info = array();
      $info['PID']  = $psfields[0];
      $info['PCPU'] = $psfields[1];
      $info['PMEM'] = $psfields[2];
      $info['SIZE'] = $psfields[3];
      $info['COMM'] = $psfields[4];
      $info['ARGS'] = $psfields[5];
      array_push($processinfo, $info);
  }
  array_shift($processinfo);
  return $processinfo;
}


function getCpuInfo() 
{       
$stat1 = file('/proc/stat');
sleep(1);
$stat2 = file('/proc/stat');
$dif = array();
$cpu = array();
$cpu = array();
for ($cpucounter = 0; substr($stat1[$cpucounter],0,3) == 'cpu'; $cpucounter++)
{
$info1 = explode(" ", preg_replace("/^[^\s]*.\s*/", "", $stat1[$cpucounter]));
$info2 = explode(" ", preg_replace("/^[^\s]*.\s*/", "", $stat2[$cpucounter]));
#var_dump ($info1);
$dif = array();
$dif[$cpucounter]['user'] = $info2[0] - $info1[0];
$dif[$cpucounter]['nice'] = $info2[1] - $info1[1];
$dif[$cpucounter]['sys'] = $info2[2] - $info1[2];
$dif[$cpucounter]['idle'] = $info2[3] - $info1[3];
$dif[$cpucounter]['iowait'] = $info2[4] - $info1[4];
$total = array_sum($dif[$cpucounter]);
foreach($dif[$cpucounter] as $x=>$y) $cpu[$cpucounter][$x] = round($y / $total * 100, 1);
}
return $cpu;
}

function getNetworkInfo() 
{
 $ipinfo = "";      
 $stat1 = file('/proc/net/dev');
 sleep(1);
 $stat2 = file('/proc/net/dev');
 $net = array();
 for ($cpucounter = 2; count($stat1) > $cpucounter; $cpucounter++)
 {
  $info1 = preg_split('/\s+/', trim($stat1[$cpucounter]));
  $info2 = preg_split('/\s+/', trim($stat2[$cpucounter]));
  $name=substr_replace("$info1[0]","",-1);
  $net[$name]["Troughput RX"] = $info2[1] - $info1[1];
  $net[$name]["Troughput TX"] = $info2[9] - $info1[9];
  $net[$name]["Bytes RX"] = $info1[1];
  $net[$name]["Bytes TX"]  = $info1[9];
  $net[$name]["Packets RX"] = $info1[2];
  $net[$name]["Packets TX"] = $info1[10];
  $net[$name]["Errors RX"] = $info1[3];
  $net[$name]["Errors TX"] = $info1[11];
  $net[$name]["Dropped RX"] = $info1[4];
  $net[$name]["Dropped TX"] = $info1[12];
  $ipinfo = array();
  exec ("ip addr show $name ", $ipinfo);
  $net[$name]["Ipv4 Address"] = preg_split('/[\s,]+/',$ipinfo[2])[2];
  $net[$name]["Ipv6 Address"] = preg_split('/[\s,]+/',$ipinfo[4])[2];
 }
 return $net;
}
 
function getNetworkConnections()
{ 
  $netstat = array();
  $netconn = array();
    
  exec ("netstat -ntuap", $netstat);
  foreach ($netstat as $line)
  {
      $psfields = preg_split("#\s+#", trim($line));
      $info['PROTO']  = $psfields[0];
      $info['RECVQ'] = $psfields[1];
      $info['SENDQ'] = $psfields[2];
      $info['LOCALADDR'] = $psfields[3];
      $info['REMOTEADDR'] = $psfields[4];
      if ($psfields[5] == "ESTABLISHED" | "TIME_WAIT" | "LISTEN")
      {
       $info['STATE'] = $psfields[5];
       $info['PROGRAM'] = $psfields[6];
      }
      else
      {
       $info['STATE'] = "";
       $info['PROGRAM'] = $psfields[5];
      }
      array_push($netconn, $info);
  }

 array_shift($netconn);
 asort($netconn);
 return $netconn;
}

function vtc($value, $warning, $critical)
{
  if (is_numeric($value))
  {
   if ($warning < $critical)
   {
     if ($value <= $warning) return 'CLASS=good'; // green
     if ($value <= $critical) return 'CLASS=warning'; // orange
     return 'CLASS=critical'; //red
   }
   else
   {
     if ($value >= $warning) return 'CLASS=good'; //green
     if ($value >= $critical) return 'CLASS=warning'; //orange
     return 'CLASS=critical'; //red
   }
  }
  else
  {  
    if (is_array($critical))
    {
     if (in_array($value, $critical)) return 'CLASS=critical'; //red
    }
    else
    {
     if (preg_match($critical, $value)) return 'CLASS=critical'; // red
    }
    if (is_array($warning))
    {
     if (in_array($value, $warning)) return 'CLASS=warning'; //red
    }
    else
    {
     if (preg_match($warning, $value)) return 'CLASS=warning'; // orange
    }
    return 'CLASS=good'; //green
  }
}


$cpuinfo=getCpuInfo();
function cpu2html()
{
 global $cpuinfo;
 echo ("<TABLE>");
 echo ("<TH COLSPAN=2>Processor</TH>");
 foreach ($cpuinfo as $key => $info)
 {
   if ($key == 0) 
   {
    echo ("<TR ".vtc(100-$info['idle'],60,80)."><TD CLASS=left>Total Usage</TD><TD>".(100-$info['idle'])." %</TD></TR>");
   }
   else
   {
    echo ("<TR ".vtc(100-$info['idle'],60,80)."><TD CLASS=left>Core ".$key." Usage</TD><TD>".(100-$info['idle'])." %</TD></TR>");
   }
 }
 echo ("</TABLE>");
}

$netinfo=getNetworkInfo();
function networkinfo2html()
{
  global $netinfo;
  foreach ($netinfo as $key => $info)
  {
    echo ("<TABLE>");
    echo ("<TH COLSPAN=2>Network ".$key."</TH>");
    echo ("<TR ".vtc($info["Ipv4 Address"],"0.0.0.0","")."><TD>Ipv4 Address</TD><TD>".$info["Ipv4 Address"]."</TD></TR>");
    echo ("<TR ".vtc($info["Ipv6 Address"],"0.0.0.0","")."><TD>Ipv6 Address</TD><TD>".$info["Ipv6 Address"]."</TD></TR>");
    echo ("<TR ".vtc($info["Troughput TX"],40000,60000)."><TD>Troughput TX</TD><TD>".$info["Troughput TX"]." kb/s</TD></TR>");
    echo ("<TR ".vtc($info["Troughput RX"],40000,60000)."><TD>Troughput RX</TD><TD>".$info["Troughput RX"]." kb/s</TD></TR>");
    echo ("<TR ".vtc($info["Bytes TX"],0,-1)."><TD>Bytes TX</TD><TD>".$info["Bytes TX"]." kb</TD></TR>");
    echo ("<TR ".vtc($info["Bytes RX"],0,-1)."><TD>Bytes RX</TD><TD>".$info["Bytes RX"]." kb</TD></TR>");
    echo ("</TABLE>");
  }
}

function networkconnections2html()
{
  $netconn=getNetworkConnections();
  echo ("<TABLE>");
  echo ("<TH COLSPAN=3>Network Connections</TH>");
    echo ("<TR><TD>Local Ip:Port</TD><TD>Remote Ip:Port</TD><TD>Program</TD></TR>");
  foreach ($netconn as $key => $info)
  {
    if ($info["STATE"] == "ESTABLISHED")
    {
     echo ("<TR ".vtc($info["LOCALADDR"],"/.*:22/","")."><TD>".$info["LOCALADDR"]."</TD><TD>".$info["REMOTEADDR"]."</TD><TD>".$info["PROGRAM"]."</TD></TR>");
    }
  }
    echo ("</TABLE>");
}

function system2html()
{
global $cpuinfo;
$loadinfo=getLoad();
echo ("<TABLE>");
echo ("<TH COLSPAN=2>System</TH>");
echo ("<TR ".vtc($loadinfo['1min'],1,2)."><TD CLASS=left>Load 1 min</TD><TD>".($loadinfo['1min']).'</TD></TR>');
echo ("<TR ".vtc($loadinfo['5min'],1,2)."><TD>Load 5 min</TD><TD>".($loadinfo['5min']).'</TD></TR>');
echo ("<TR ".vtc($loadinfo['15min'],1,2)."><TD>Load 15 min</TD><TD>".($loadinfo['15min']).'</TD></TR>');
echo ("<TR ".vtc($cpuinfo[0]['iowait'],60,80)."><TD>IO-Wait</TD><TD>".($cpuinfo[0]['iowait']).' %</TD></TR>');
echo ('</TABLE>');
}

function memory2html()
{
$systemMemInfo=getSystemMemInfo();
$useablemem=$systemMemInfo['MemFree']+$systemMemInfo['Buffers']+$systemMemInfo['Cached'];
echo ("<TABLE>");
echo ("<TH COLSPAN=2>Memory</TH>");
echo ("<TR ".vtc($useablemem,300,100)."><TD CLASS=left>Total</TD><TD>".$systemMemInfo['MemTotal']." MB</TD></TR>");
echo ("<TR ".vtc($useablemem,300,100)."><TD>Useable</TD><TD>$useablemem MB</TD></TR>");
echo ("<TR ".vtc($systemMemInfo['MemFree'],10,1)."><TD>Free</TD><TD>".$systemMemInfo['MemFree']." MB</TD></TR>");
echo ("<TR ".vtc($systemMemInfo['Buffers'],5,0)."><TD>Buffers</TD><TD>".$systemMemInfo['Buffers']." MB</TD></TR>");
echo ("<TR ".vtc($systemMemInfo['Cached'],5,0)."><TD>Cache</TD><TD>".$systemMemInfo['Cached']." MB</TD></TR>");
echo ("</TABLE>");
}

function diskspace2html ($device, $path, $disklabel, $warning, $critical, $unit)
{
$devider = 0;
switch($unit)
{
    case 'kB';
        $devider = 1;
    break;
    case 'MB';
        $devider = 2;
    break;
    case 'GB';
        $devider = 3;
    break;
    case 'TB';
        $devider = 4;
    break;
}

$diskfree=round(disk_free_space($path) / (pow(1024, $devider)), 1);
$disktotal=round(disk_total_space($path) / (pow (1024, $devider)) , 1);
$diskbgcolor=vtc($diskfree, $warning, $critical);
global $firstcolumnwidth, $tablewidth;

echo ("<TABLE BORDER=1 WIDTH=$tablewidth>");
echo ("<TH COLSPAN=2>Disk $disklabel</TH>");
echo ("<TR $diskbgcolor><TD CLASS=left>Usage</TD><TD>".(int)(100/$disktotal*($disktotal-$diskfree))." %</TD></TR>");
echo ("<TR $diskbgcolor><TD>Size</TD><TD>$disktotal $unit</TD></TR>");
echo ("<TR $diskbgcolor><TD>Free</TD><TD>$diskfree $unit</TD></TR>");

if ($device != "")
{
$smart = array();
exec ("/usr/sbin/smartctl $device -x", $smart);

$temperature = 0;
$powerstate = 0;

foreach ($smart as $line)
{
  if (strpos ($line, "Temperature_Celsius") !== false) $temperature = preg_replace("/.* /","",$line);
  if (strpos ($line, "Device State:") !== false)
  {
   $powerstate = preg_replace("/.*: */","",$line);
   $powerstate = preg_replace("/ \(.*/","",$powerstate);
   }
}

echo ("<TR ".vtc($temperature,40,50)."><TD CLASS=left>Temperature</TD><TD>$temperature  &deg;C</TD></TR>");
echo ("<TR ".vtc($powerstate,"Active","")."><TD CLASS=left>Power State</TD><TD>$powerstate</TD></TR>");
}

echo ("</TABLE>");


}

function processstatus2html ()
{
    global $firstcolumnwidth, $tablewidth;
    echo ("<TABLE BORDER=1 WIDTH=$tablewidth>");
    echo ("<TH COLSPAN=5>Process</TH>");
    echo ("<TR><TD>Command</TD><TD>Process Id</TD><TD>%CPU</TD><TD>%MEM</TD><TD>Size</TD></TR>");

    $processinfo = getProcessInfo();
    foreach ($processinfo as $key => $value)
    {
       echo ("<TR ".vtc($value['PCPU'],50,90)."><TD>".$value['COMM']."</TD><TD>".$value['PID']."</TD><TD>".$value['PCPU']."</TD><TD>".$value['PMEM']."</TD><TD>".$value['SIZE']."</TD></TR>");
    }
}

function hostinfo2html()
{
 echo "<TABLE><TR ><TH COLSPAN=2>Identification</TH></TR>";
 echo "<TR ".vtc(gethostname(),"","")."><TD CLASS=left>Hostname:</TD><TD>".gethostname()."</TD></TR>";
 $externalip = file_get_contents('http://phihag.de/ip/');;
 echo "<TR ".vtc($externalip,"","")."><TD>External Ip:</TD><TD>$externalip</TD></TR></TABLE>";
}
?>
