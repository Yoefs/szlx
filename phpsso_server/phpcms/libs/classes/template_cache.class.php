<?php
/**
 *  模板解析缓存
 */
final class template_cache {
	
	/**
	 * 编译模板
	 *
	 * @param $module	模块名称
	 * @param $template	模板文件名
	 * @param $istag	是否为标签模板
	 * @return unknown
	 */
	
	public function template_compile($module, $template, $style = 'default') {

		$tplfile = $_tpl = PC_PATH.'templates/'.$style.'/'.$module.'/'.$template.'.html';
		if ($style != 'default' && ! file_exists ( $tplfile )) {
			$tplfile = PC_PATH.'templates/default/'.$module.'/'.$template.'.html';
		}
		
		if (! file_exists ( $tplfile )) {

			showmessage ( "$_tpl is not exists!" );
		}
		$content = @file_get_contents ( $tplfile );
		
		$filepath = PHPCMS_PATH.'caches/caches_template/'.$module.'/';
	    if(!is_dir($filepath)) {
			mkdir($filepath, 0777, true);
	    }
		$compiledtplfile = $filepath.$template.'.'.$style.'.admin';
		$content = $this->template_parse($content);
		$strlen = file_put_contents ( $compiledtplfile, $content );
		chmod ( $compiledtplfile, 0777 );

		return $strlen;
	}
	public function template_compile_admin($module, $template) {

		$tplfile = $_tpl = PC_PATH.'templates/admin/'.$module.'/'.$template.'.html';
		$content = @file_get_contents ( $tplfile );
		$filepath = PHPCMS_PATH.'caches/caches_template/admintpl/'.$module.'/';
	    if(!is_dir($filepath)) {
			mkdir($filepath, 0777, true);
	    }
		$compiledtplfile = $filepath.$template.'.admin';
		$content = $this->template_parse($content);
		$strlen = file_put_contents ( $compiledtplfile, $content );
		chmod ( $compiledtplfile, 0777 );
		return $strlen;
	}
	/**
	 * 更新模板缓存
	 *
	 * @param $tplfile	模板原文件路径
	 * @param $compiledtplfile	编译完成后，写入文件名
	 * @return $strlen 长度
	 */
	public function template_refresh($tplfile, $compiledtplfile) {
		$str = @file_get_contents ($tplfile);
		$str = $this->template_parse ($str);
		$strlen = file_put_contents ($compiledtplfile, $str );
		chmod ($compiledtplfile, 0777);
		return $strlen;
	}
	/**
	 * 更新指定模块模板缓存
	 *
	 * @param $module	模块名称
	 * @return ture
	 */
	public function template_module($module) {
		$files = glob ( TPL_ROOT . TPL_NAME . '/' . $module . '/*.html' );
		if (is_array ( $files )) {
			foreach ( $files as $tpl ) {
				$template = str_replace ( '.html', '', basename ( $tpl ) );
				$this->template_compile ( $module, $template );
			}
		}
		return TRUE;
	}
	/**
	 * 更新所有模板缓存
	 *
	 * @return ture
	 */
	public function template_cache() {
		global $MODULE;
		if(!is_array($MODULE)) return FALSE;
		foreach ( $MODULE as $module => $m ) {
			$this->template_module ( $module );
		}
		return TRUE;
	}

	/**
	 * 解析模板
	 *
	 * @param $str	模板内容
	 * @param $istag	是否为标签模板
	 * @return ture
	 */
	public function template_parse($str, $istag = 0) {
		$str = preg_replace ( "/([\n\r]+)\t+/s", "\\1", $str );
		$str = preg_replace ( "/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $str );
		$str = preg_replace ( "/\{template\s+(.+)\}/", "<?admin include template(\\1); ?>", $str );
		$str = preg_replace ( "/\{include\s+(.+)\}/", "<?admin include \\1; ?>", $str );
		$str = preg_replace ( "/\{admin\s+(.+)\}/", "<?admin \\1?>", $str );
		$str = preg_replace ( "/\{if\s+(.+?)\}/", "<?admin if(\\1) { ?>", $str );
		$str = preg_replace ( "/\{else\}/", "<?admin } else { ?>", $str );
		$str = preg_replace ( "/\{elseif\s+(.+?)\}/", "<?admin } elseif (\\1) { ?>", $str );
		$str = preg_replace ( "/\{\/if\}/", "<?admin } ?>", $str );
		//for 循环
		$str = preg_replace("/\{for\s+(.+?)\}/","<?admin for(\\1) { ?>",$str);
		$str = preg_replace("/\{\/for\}/","<?admin } ?>",$str);
		//++ --
		$str = preg_replace("/\{\+\+(.+?)\}/","<?admin ++\\1; ?>",$str);
		$str = preg_replace("/\{\-\-(.+?)\}/","<?admin ++\\1; ?>",$str);
		$str = preg_replace("/\{(.+?)\+\+\}/","<?admin \\1++; ?>",$str);
		$str = preg_replace("/\{(.+?)\-\-\}/","<?admin \\1--; ?>",$str);
		$str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\}/", "<?admin if(is_array(\\1)) foreach(\\1 AS \\2) { ?>", $str );
		$str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?admin if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>", $str );
		$str = preg_replace ( "/\{\/loop\}/", "<?admin } ?>", $str );
		$str = preg_replace ( "/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?admin echo \\1;?>", $str );
		$str = preg_replace ( "/\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?admin echo \\1;?>", $str );
		$str = preg_replace ( "/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?admin echo \\1;?>", $str );
		$str = preg_replace_callback("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s",  array($this, 'addquote'),$str);
		$str = preg_replace ( "/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?admin echo \\1;?>", $str );
		if (! $istag)
			$str = "<?admin defined('IN_PHPCMS') or exit('No permission resources.'); ?>" . $str;
		return $str;
	}

	/**
	 * 转义 // 为 /
	 *
	 * @param $var	转义的字符
	 * @return 转义后的字符
	 */
	public function addquote($matches) {
		$var = '<?admin echo '.$matches[1].';?>';
		return str_replace ( "\\\"", "\"", preg_replace ( "/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var ) );
	}
}
?>