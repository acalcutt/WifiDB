<?php
/////////////////////////////////////////////////////////////////
//  By: Phillip Ferland (Longbow486)                           //
//  Email: longbow486@msn.com                                  //
//  Started on: 10.14.07                                       //
//  Purpose: To generate a PNG graph of a WAP's signals        //
//           from URL driven data                              //
//  Filename: details.php								   //
//	License: GLPv2			                                   //
/////////////////////////////////////////////////////////////////

include('functions.php');
$lastedit="2010-June-13";
?>
<title>Vistumbler to PNG Signal Graph <?php echo $ver['wifi']; ?> Beta - ---RanInt---</title>
<link rel="stylesheet" href="/css/site4.0.css">
<body topmargin="10" leftmargin="0" rightmargin="0" bottommargin="10" marginwidth="10" marginheight="10">
<div align="center">
<table border="0" width="75%" cellspacing="10" cellpadding="2">
	<tr>
		<td bgcolor="#315573">
		<p align="center"><b><font size="5" face="Arial" color="#FFFFFF">
		Vistumbler to PNG Ver <?php echo $ver['wifi']." Beta"; ?> </font>
		</b>
		</td>
	</tr>
</table>
</div>
<div align="center">
<table border="0" width="75%" cellspacing="10" cellpadding="2" height="90">
	<tr>
<td width="17%" bgcolor="#304D80" valign="top">
<?php
#PUT YOUR LINKS HERE#
?>
</td>
		<td width="80%" bgcolor="#A9C6FA" valign="top" align="center">
			<p align="center">

<h2>Vistumbler to PNG Signal Grapher</h2><br/>
<?php echo $ver.["wifi"]; ?> Beta<br>
These Are sample images<br />
<img src="sample.png"><br>
<img src="vsample.png"><br>
Source is right <a href="http://vistumbler.svn.sourceforge.net/viewvc/vistumbler/wifi">here</a><br>
You can also see the Version History <a href="ver.php">here.</a><br>
<a href="http://forum.techidiots.net/forum/viewforum.php?f=22">Forum</a><br>
<font size="1">[located at Techidiots.net]<br><br>Please use Vistumbler to gather the data.<br>
You can get it <a href="http://www.vistumbler.net" target="_blank">here</a>
</td>
</tr>
<tr>
<td bgcolor="#315573" height="23"></td>
<td bgcolor="#315573" width="0">

</td>
</tr>
</table>
</div>
</html>
