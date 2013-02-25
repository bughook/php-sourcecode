<?php
BugHook_GetSource::setBaseDirectory(YOUR_WORKING_DIRECTORY_HERE);

class BugHook_GetSource {

	private static $base_directory;

	public function getSource() {
		// get parameters
		$source_control_system = (isset($_REQUEST['scs']))?$_REQUEST['scs']:null;
		$branch = (isset($_REQUEST['branch']))?$_REQUEST['branch']:'master';
		$filename = isset($_REQUEST['filename'])?urldecode($_REQUEST['filename']):null;
		$line = isset($_REQUEST['line'])?(intval($_REQUEST['line'])-1):null;
		$timestamp = isset($_REQUEST['timestamp'])?urldecode($_REQUEST['timestamp']):null;

		// if parameters are empty - stop here
		if(empty($branch) || empty($filename) || empty($timestamp) || !in_array($source_control_system, array('git','svn'))) {
			echo 'false';
			exit();
		}

		// change working directory
		chdir(self::$base_directory);

		// prepare command depending on source code control system
		$timestamp = date("Y-m-d H:i:s", strtotime($timestamp));
		switch($source_control_system) {
			case 'git':
				$command = "git show ".escapeshellarg($branch)."@{".escapeshellarg($timestamp)."}:".escapeshellarg($filename);
				break;

			case 'svn':
				$command = "svn cat -r {".escapeshellarg($timestamp)."} ".escapeshellarg($filename);
				break;
		}

		// run command
		ob_start();
		passthru($command, $cmd_result);
		$filecontent = ob_get_contents();
		ob_end_clean();

		// command failed
		if($cmd_result > 0) {
			echo 'false';
			exit();
		}

		// normalize command result
		$filecontent = str_replace("\r\n", "\n", $filecontent);
		$filecontent = explode("\n", $filecontent);

		// get need lines of code
		$result = array();
		for($i=($line-5);$i<=($line+5);$i++) {
			$result[] = $filecontent[$i];
		}

		// return code
		echo implode("\n",$result);
	}

	public static function setBaseDirectory($value) {
		self::$base_directory = $value;
	}
}
$getsource = new BugHook_GetSource();
$getsource->getSource();