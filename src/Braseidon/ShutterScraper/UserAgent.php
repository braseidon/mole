<?php namespace Braseidon\ShutterScraper;

class UserAgent {

	/**
	 * Function to generate a random user agent
	 *
	 * @return string
	 */
	public static function generate()
	{
		$netClr = [];
		$sysNtv = [];
		$agents = [];

		$randOne = mt_rand(0,9);
		$randTwo = mt_rand(0,255);

		$date = date("YmdHis").$randTwo;

		$netClr[0] = ".NET CLR 2.0.50727";
		$netClr[1] = ".NET CLR 1.1.4322";
		$netClr[2] = ".NET CLR 4.0.30319";
		$netClr[3] = ".NET CLR 3.5.2644";
		$netClr[4] = ".NET CLR 1.0.10322";
		$netClr[5] = ".NET CLR 3.5.11952";
		$netClr[6] = ".NET CLR 4.0.30319";
		$netClr[7] = ".NET CLR 2.0.65263";
		$netClr[8] = ".NET CLR 1.1.4322; .NET CLR 4.0.30319";
		$netClr[9] = ".NET CLR 4.0.30319; .NET CLR 2.0.50727";

		$sysNtv[0] = "Windows NT 6.1; WOW64";
		$sysNtv[1] = "Windows NT 5.1; rv:10.1";
		$sysNtv[2] = "Windows NT 5.1; U; en";
		$sysNtv[3] = "compatible; MSIE 10.0; Windows NT 6.2";
		$sysNtv[4] = "Windows NT 6.1; U; en; OneNote.2; ";
		$sysNtv[5] = "compatible; Windows NT 6.2; WOW64; en-US";
		$sysNtv[6] = "compatible; MSIE 10.0; Windows NT 6.2; Trident/5.0; WOW64";
		$sysNtv[7] = "Windows NT 5.1; en; FDM";
		$sysNtv[8] = "Windows NT 6.2; WOW64; MediaBox 1.1";
		$sysNtv[9] = "compatible; MSIE 11.0; Windows NT 6.2; WOW64";

		// User agents that are highly randomized
		$agents[0] = "Opera/9.80 (" . $sysNtv[$randOne] . ";" . $netClr[$randOne] . ") Presto/2.10." . mt_rand(0,999) . " Version/11.62";
		$agents[1] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ";" . $netClr[$randOne] . ") Gecko/" . $date . " Firefox/23.0." . $randOne;
		$agents[2] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ";" . $netClr[$randOne] . ") AppleWebKit/535.2 (KHTML, like Gecko) Chrome/20.0." . mt_rand(0,9999) . "." . mt_rand(0,99) . " Safari/535.2";
		$agents[3] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ";" . $netClr[$randOne] . ")";
		$agents[4] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ") AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0." . mt_rand(0,9999) . "." . mt_rand(0,99) . " Safari/537.36)";
		$agents[5] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ";" . $netClr[$randOne] . ")";
		$agents[6] = "Opera/9.80 (" . $sysNtv[$randOne] . ") Presto/2.9." . $randTwo." Version/12.50";
		$agents[7] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ";" . $netClr[$randOne] . ")";
		$agents[8] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ") Gecko/" . $date." Firefox/17.0";
		$agents[9] = "Mozilla/5.0 (" . $sysNtv[$randOne] . ";" . $netClr[$randOne] . ")";

		return $agents[$randOne];
	}
}