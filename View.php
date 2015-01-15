<?php
/**
 * Created by PhpStorm.
 * User: user1
 * Date: 15.01.15
 * Time: 14:44
 */

namespace common;


use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

class View extends \yii\web\View {

	private $requireJsFiles = [];
	private $requireJsCode = [];

	const POS_END_REQUIRED = 6;

	/**
	 * @inheritdoc
	 */
	protected function renderBodyEndHtml($ajaxMode)
	{
		$this->getJsData();
		$lines = parent::renderBodyEndHtml($ajaxMode);

		$paths = $this->getRequireJsPaths();
		$requireJsConfig = $this->getRequireJsConfig($paths);
		$jsCode = $this->getRequireJsCode();

		$lines = "<script type=\"text/javascript\">var require = {$requireJsConfig};</script>\n" . $lines;
		$modulesJsArray = json_encode(array_keys($paths));
		$lines .= "<script type=\"text/javascript\">require(['jquery'], function() { require({$modulesJsArray}, function() { {$jsCode} }); });</script>\n";
		return $lines;
	}

	/**
	 * Gets js data for requireJs (files, code) and prevents it from Yii insert
	 */
	protected function getJsData()
	{
		$this->requireJsFiles[self::POS_END] = isset($this->jsFiles[self::POS_END]) ? $this->jsFiles[self::POS_END] : [];
		$this->jsFiles[self::POS_END] = isset($this->jsFiles[self::POS_END_REQUIRED]) ? $this->jsFiles[self::POS_END_REQUIRED] : [];
		if(isset($this->js[self::POS_READY])) {
			$this->requireJsCode[self::POS_READY] = $this->js[self::POS_READY];
			unset($this->js[self::POS_READY]);
		}
		if(isset($this->js[self::POS_LOAD])) {
			$this->requireJsCode[self::POS_LOAD] = $this->js[self::POS_LOAD];
			unset($this->js[self::POS_LOAD]);
		}
	}

	protected function getRequireJsPaths() {
		$paths = [];
		$i = 0;
		foreach($this->requireJsFiles as $pos => $files) {
			foreach($files as $file => $htmlCode) {
				$paths['yiiAsset' . $i++][] = preg_replace('#\.js$#', '', $file);
			}
		}
		return $paths;
	}

	protected function getRequireJsConfig($paths)
	{
		return json_encode([
			'paths' => $paths,
			'baseUrl' => '/',
		]);
	}

	protected function getRequireJsCode()
	{
		$wrapLines = [];
		if (!empty($this->requireJsCode[self::POS_READY]))
			$wrapLines[] = "jQuery(document).ready(function () {\n" . implode("\n", $this->requireJsCode[self::POS_READY]) . "\n});";
		if (!empty($this->requireJsCode[self::POS_LOAD]))
			$wrapLines[] = "jQuery(window).load(function () {\n" . implode("\n", $this->requireJsCode[self::POS_LOAD]) . "\n});";
		return implode("\n", $wrapLines);
	}


}