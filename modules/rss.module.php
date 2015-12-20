<?php

(!defined('IN_PowerBB')) ? die() : '';

$CALL_SYSTEM				=	array();
$CALL_SYSTEM['SUBJECT'] 	= 	true;
$CALL_SYSTEM['SECTION'] 	= 	true;
include('common.php');

define('CLASS_NAME','PowerBBRSSMOD');

class PowerBBRSSMOD
{
	function run()
	{
	global $PowerBB;
 	 if (!$PowerBB->_CONF['info_row']['active_rss'])
	 {
			@header("Location: index.php");
			exit;
	 }
		$charset                =   $PowerBB->_CONF['info_row']['charset'];
	   // $datenow                =   date("D, d M Y H:i:s");
		$datenow                =   date(DATE_RFC2822);

	$PowerBB->_GET['subject'] = $PowerBB->functions->CleanVariable($PowerBB->_GET['subject'],'intval');
	$PowerBB->_GET['section'] = $PowerBB->functions->CleanVariable($PowerBB->_GET['section'],'intval');
	$PowerBB->_GET['id'] = $PowerBB->functions->CleanVariable($PowerBB->_GET['id'],'intval');

		if ($PowerBB->_GET['subject'])
		{
		$Forumtitle                =   $PowerBB->_CONF['info_row']['title'];
		}
		elseif ($PowerBB->_GET['section'])
		{
		// Get section information and set it in $this->Section
		$SecArr 		= 	array();
		$SecArr['where'] 	= 	array('id',$PowerBB->_GET['id']);

		$Section = $PowerBB->core->GetInfo($SecArr,'section');
		$Forumtitle                =   $PowerBB->_CONF['info_row']['title'] .' - ' .$Section['title']." - " .$Section['section_describe'];
		}
 		$PowerBB->_CONF['info_row']['title'] 	= 	$PowerBB->functions->CleanVariable($PowerBB->_CONF['info_row']['title'],'html');
		$PowerBB->_CONF['info_row']['title'] 	= 	$PowerBB->functions->CleanVariable($PowerBB->_CONF['info_row']['title'],'sql');
		echo "<?xml version=\"1.0\" encoding=\"$charset\"?>\n\n";
		echo "<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n";
		echo "<channel>\n";
		echo "	<title>" . $PowerBB->_CONF['info_row']['title'] . "</title>\n";
		echo "	<link>" . $PowerBB->functions->GetForumAdress() . "</link>\n";
        echo "<pubDate>" . $datenow . "</pubDate>\n";
		echo "	<description>".$PowerBB->_CONF['template']['_CONF']['lang']['Abstracts_another_active_topics_in'] . ":".$Forumtitle ."</description>\n";
		echo "	<language>" . $PowerBB->_CONF['info_row']['content_language'] . "</language>\n";
		echo "	<lastBuildDate>" . $datenow . "</lastBuildDate>\n";
		echo "	<generator>pbboard</generator>\n";
		echo "	<ttl>60</ttl>\n";
		if ($PowerBB->_GET['subject'])
		{
			$this->_SubjectRSS();
		}
		elseif ($PowerBB->_GET['section'])
		{
			$this->_SectionRSS();
		}
		echo "	</channel>\n";
		echo "</rss>\n";



	}

	function _SubjectRSS()
	{
	global $PowerBB;

	$SubjectArr = array();

	$SubjectArr['where'] 		= 	array();

	$SubjectArr['where'][0] 		= 	array();
	$SubjectArr['where'][0]['name'] 	= 	'review_subject<>1 AND sec_subject<>1 AND delete_topic';
	$SubjectArr['where'][0]['oper'] 	= 	'<>';
	$SubjectArr['where'][0]['value'] 	= 	'1';

	$SubjectArr['order'] 		= 	array();
	$SubjectArr['order']['field'] 	= 	'write_time';
	$SubjectArr['order']['type'] 	= 	'DESC';

	$SubjectArr['limit'] 		= 	'10';

	$SubjectArr['proc'] 		= 	array();
	// Ok Mr.XSS go to hell !
	$SubjectArr['proc']['*'] 	= 	array('method'=>'clean','param'=>'html');
	$SubjectArr['proc']['native_write_time'] 	= 	array('method'=>'date','store'=>'write_date','type'=>$datenow);
	$SubjectArr['proc']['write_time'] 			= 	array('method'=>'date','store'=>'reply_date','type'=>$datenow);

	$SubjectList = $PowerBB->core->GetList($SubjectArr,'subject');

	$size 	= 	sizeof($SubjectList);
	$x	=	0;

	while ($x < $size)
	{

        $SubjectList[$x]['text'] = $PowerBB->Powerparse->replace($SubjectList[$x]['text']);
        $SubjectList[$x]['text'] = $PowerBB->Powerparse->censor_words($SubjectList[$x]['text']);

		$PowerBB->Powerparse->replace_smiles($SubjectList[$x]['text']);

		// $bad_characters: All ASCII characters below ASCII 32 (except 9, 10 and 13 (tab, newline and carrige return)).
		$bad_characters = array_diff(range(chr(0), chr(31)), array(chr(9), chr(10), chr(13)));
		$SubjectList[$x]['text'] = str_replace($bad_characters, "", $SubjectList[$x]['text']);
		$SubjectList[$x]['title'] = str_replace($bad_characters, "", $SubjectList[$x]['title']);
       	$SubjectList[$x]['text'] = str_replace($PowerBB->_CONF['template']['_CONF']['lang']['resize_image_w_h'], "", $SubjectList[$x]['text']);

		$description = strip_tags($SubjectList[$x]['text']);
        $censorwords = preg_split('#[ \r\n\t]+#', $PowerBB->_CONF['info_row']['censorwords'], -1, PREG_SPLIT_NO_EMPTY);
 		$SubjectList[$x]['title'] 	= 	$PowerBB->functions->CleanVariable($SubjectList[$x]['title'],'html');
		$SubjectList[$x]['title'] 	= 	$PowerBB->functions->CleanVariable($SubjectList[$x]['title'],'sql');
        $SubjectList[$x]['text'] = str_ireplace($censorwords,'**', $SubjectList[$x]['text']);
        $SubjectList[$x]['title'] = $PowerBB->Powerparse->censor_words($SubjectList[$x]['title']);
      $SubjectList[$x]['write_date'] = $PowerBB->functions->date($SubjectList[$x]['write_date']);

		$extention = "";
		$url = "index.php?page=topic&amp;show=1&amp;id=";
		$url = $PowerBB->functions->rewriterule($url);
		echo "	<item>\n";
		echo "		<title>" . $SubjectList[$x]['title'] . "</title>\n";
		echo "		<link>" . $PowerBB->functions->GetForumAdress() . $url . $SubjectList[$x]['id'] . $extention . "</link>\n";
		echo '		<pubDate>' . date("r", $SubjectList[$x]['write_time']) . '</pubDate>' . "\n";
		echo "		<description><![CDATA[" . $description . "]]></description>\n";
		echo "		<content:encoded><![CDATA[" . $SubjectList[$x]['text'] . "]]></content:encoded>\n";
		echo "		<dc:creator><![CDATA[" . $SubjectList[$x]['writer'] . "]]></dc:creator>\n";
		echo "		<guid isPermaLink=\"true\">" . $PowerBB->functions->GetForumAdress() . $url . $SubjectList[$x]['id'] . $extention . "</guid>\n";
		echo "	</item>\n";

		$x += 1;
	}
	}

	function _SectionRSS()
	{
	global $PowerBB;

	// Clean id from any strings
	$PowerBB->_GET['id'] = $PowerBB->functions->CleanVariable($PowerBB->_GET['id'],'intval');


	// Get section information and set it in $this->Section
	$SecArr 		= 	array();
	$SecArr['where'] 	= 	array('id',$PowerBB->_GET['id']);

	$Section = $PowerBB->core->GetInfo($SecArr,'section');
			@include("cache/sectiongroup_cache".$PowerBB->_GET['id'].".php");
			$groups = unserialize(base64_decode($sectiongroup_cache));
			if ($groups[$PowerBB->_CONF['group_info']['id']]['view_section'] == 0)
			{
			    $Section['hide_subject'] = '1';
			}
			elseif ($groups[$PowerBB->_CONF['group_info']['id']]['view_subject'] == 0)
			{
			$Section['hide_subject']	= '1';
			}
	       // No section if hide subject error :)
		if ($Section['hide_subject'] == '1')
		{
		echo '	<item>';
		echo '		<title>' . $PowerBB->_CONF['template']['_CONF']['lang']['can_not_view_section'] . '</title>';
		echo '		<link>' . $PowerBB->_CONF['template']['_CONF']['lang']['can_not_view_section'] . '</link>';
		echo '		<description>' . $PowerBB->_CONF['template']['_CONF']['lang']['can_not_view_section'] . '</description>';
		echo '	</item>';

		$PowerBB->functions->error($PowerBB->_CONF['template']['_CONF']['lang']['can_not_view_section']);

		}

	// No _GET['id'] , so ? show a small error :)
		if (!$Section)
		{
		echo '	<item>';
		echo '		<title>' . $PowerBB->_CONF['template']['_CONF']['lang']['path_not_true'] . '</title>';
		echo '		<link>' . $PowerBB->_CONF['template']['_CONF']['lang']['path_not_true'] . '</link>';
		echo '		<description>' . $PowerBB->_CONF['template']['_CONF']['lang']['path_not_true'] . '</description>';
		echo '	</item>';
		}

	$SubjectArr = array();

	$SubjectArr['where'] 		= 	array();

	$SubjectArr['where'][0] 		= 	array();
	$SubjectArr['where'][0]['name'] 	= 	'section';
	$SubjectArr['where'][0]['oper'] 	= 	'=';
	$SubjectArr['where'][0]['value'] 	= 	$PowerBB->_GET['id'];

	$SubjectArr['where'][1] 		    = 	array();
	$SubjectArr['where'][1]['con']	    =	'AND';
	$SubjectArr['where'][1]['name'] 	= 	'review_subject<>1 AND sec_subject<>1 AND delete_topic';
	$SubjectArr['where'][1]['oper'] 	= 	'<>';
	$SubjectArr['where'][1]['value'] 	= 	'1';

	$SubjectArr['order'] 		= 	array();
	$SubjectArr['order']['field'] 	= 	'write_time';
	$SubjectArr['order']['type'] 	= 	'DESC';

	$SubjectArr['limit'] 		= 	'20';

	$SubjectArr['proc'] 		= 	array();
	// Ok Mr.XSS go to hell !
	$SubjectArr['proc']['*'] 	= 	array('method'=>'clean','param'=>'html');


	$SubjectList = $PowerBB->core->GetList($SubjectArr,'subject');

	$size 	= 	sizeof($SubjectList);
	$x	=	0;

	while ($x < $size)
	{

 		$SubjectList[$x]['title'] 	= 	$PowerBB->functions->CleanVariable($SubjectList[$x]['title'],'html');
		$SubjectList[$x]['title'] 	= 	$PowerBB->functions->CleanVariable($SubjectList[$x]['title'],'sql');
        $SubjectList[$x]['text'] = $PowerBB->Powerparse->replace($SubjectList[$x]['text']);
		$PowerBB->Powerparse->replace_smiles($SubjectList[$x]['text']);
        $SubjectList[$x]['text'] = $PowerBB->Powerparse->censor_words($SubjectList[$x]['text']);
        $SubjectList[$x]['title'] = $PowerBB->Powerparse->censor_words($SubjectList[$x]['title']);
		$SubjectList[$x]['text'] =str_ireplace("\n","<br />",$SubjectList[$x]['text']);

		// $bad_characters: All ASCII characters below ASCII 32 (except 9, 10 and 13 (tab, newline and carrige return)).
		$bad_characters = array_diff(range(chr(0), chr(31)), array(chr(9), chr(10), chr(13)));
		$SubjectList[$x]['text'] = str_replace($bad_characters, "", $SubjectList[$x]['text']);
		$SubjectList[$x]['title'] = str_replace($bad_characters, "", $SubjectList[$x]['title']);
       	$SubjectList[$x]['text'] = str_replace($PowerBB->_CONF['template']['_CONF']['lang']['resize_image_w_h'], "", $SubjectList[$x]['text']);

     	$description = strip_tags($SubjectList[$x]['text']);
        $censorwords = preg_split('#[ \r\n\t]+#', $PowerBB->_CONF['info_row']['censorwords'], -1, PREG_SPLIT_NO_EMPTY);
        $SubjectList[$x]['text'] = str_ireplace($censorwords,'**', $SubjectList[$x]['text']);

		$extention = "";
		$url = "index.php?page=topic&amp;show=1&amp;id=";
		$url = $PowerBB->functions->rewriterule($url);

		echo "	<item>";
		echo "		<title>" . $SubjectList[$x]['title'] . "</title>\n";
		echo "		<link>" . $PowerBB->functions->GetForumAdress() . $url . $SubjectList[$x]['id'] . $extention . "</link>\n";
		echo '		<pubDate>' . date("r", $SubjectList[$x]['write_time']) . '</pubDate>' . "\n";
		echo "		<description><![CDATA[" . $description . "]]></description>\n";
		echo "		<content:encoded><![CDATA[" . $SubjectList[$x]['text'] . "]]></content:encoded>\n";
		echo "		<dc:creator><![CDATA[" . $SubjectList[$x]['writer'] . "]]></dc:creator>\n";
		echo "		<guid isPermaLink=\"true\">" . $PowerBB->functions->GetForumAdress() . $url . $SubjectList[$x]['id'] . $extention . "</guid>\n";
		echo "	</item>\n";

		$x += 1;
	}
	}
}

?>