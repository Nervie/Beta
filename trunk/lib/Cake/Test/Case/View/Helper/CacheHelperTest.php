<?php
/**
 * CacheHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('Model', 'Model');
App::uses('View', 'View');
App::uses('CacheHelper', 'View/Helper');

/**
 * CacheTestController class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class CacheTestController extends Controller {

/**
 * helpers property
 *
 * @var array
 * @access public
 */
	public $helpers = array('Html', 'Cache');

/**
 * cache_parsing method
 *
 * @access public
 * @return void
 */
	public function cache_parsing() {
		$this->viewPath = 'Posts';
		$this->layout = 'cache_layout';
		$this->set('variable', 'variableValue');
		$this->set('superman', 'clark kent');
		$this->set('batman', 'bruce wayne');
		$this->set('spiderman', 'peter parker');
	}
}

/**
 * CacheHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class CacheHelperTest extends CakeTestCase {

/**
 * Checks if TMP/views is writable, and skips the case if it is not.
 *
 * @return void
 */
	public function skip() {
		$this->skipUnless(is_writable(TMP . 'cache' . DS . 'views' . DS), 'TMP/views is not writable %s');
	}
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$_GET = array();
		$request = new CakeRequest();
		$this->Controller = new CacheTestController($request);
		$View = new View($this->Controller);
		$this->Cache = new CacheHelper($View);
		Configure::write('Cache.check', true);
		Configure::write('Cache.disable', false);
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		), true);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	public function tearDown() {
		clearCache();
		unset($this->Cache);
		parent::tearDown();
	}

/**
 * test cache parsing with no cake:nocache tags in view file.
 *
 * @access public
 * @return void
 */
	public function testLayoutCacheParsingNoTagsInView() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->request->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('index');
		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertPattern('/php echo \$variable/', $contents);
		$this->assertPattern('/php echo microtime()/', $contents);
		$this->assertPattern('/clark kent/', $result);

		@unlink($filename);
	}

/**
 * test cache parsing with non-latin characters in current route
 *
 * @access public
 * @return void
 */
	public function testCacheNonLatinCharactersInRoute() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array('風街ろまん'),
			'named' => array()
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/posts/view/風街ろまん';
		$this->Controller->action = 'view';

		$View = new View($this->Controller);
		$result = $View->render('index');

		$filename = CACHE . 'views' . DS . 'posts_view_風街ろまん.php';
		$this->assertTrue(file_exists($filename));

		@unlink($filename);
	}
/**
 * Test cache parsing with cake:nocache tags in view file.
 *
 * @access public
 * @return void
 */
	public function testLayoutCacheParsingWithTagsInView() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('test_nocache_tags');
		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertPattern('/if \(is_writable\(TMP\)\)\:/', $contents);
		$this->assertPattern('/php echo \$variable/', $contents);
		$this->assertPattern('/php echo microtime()/', $contents);
		$this->assertNoPattern('/cake:nocache/', $contents);

		@unlink($filename);
	}

/**
 * test that multiple <!--nocache--> tags function with multiple nocache tags in the layout.
 *
 * @return void
 */
	public function testMultipleNoCacheTagsInViewfile() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('multiple_nocache');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertNoPattern('/cake:nocache/', $contents);
		@unlink($filename);
	}

/**
 * testComplexNoCache method
 *
 * @return void
 */
	public function testComplexNoCache () {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_complex',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		));
		$this->Controller->cacheAction = array('cache_complex' => 21600);
		$this->Controller->request->here = '/cacheTest/cache_complex';
		$this->Controller->action = 'cache_complex';
		$this->Controller->layout = 'multi_cache';
		$this->Controller->viewPath = 'Posts';

		$View = new View($this->Controller);
		$result = $View->render('sequencial_nocache');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);
		$this->assertPattern('/A\. Layout Before Content/', $result);
		$this->assertPattern('/B\. In Plain Element/', $result);
		$this->assertPattern('/C\. Layout After Test Element/', $result);
		$this->assertPattern('/D\. In View File/', $result);
		$this->assertPattern('/E\. Layout After Content/', $result);
		//$this->assertPattern('/F\. In Element With No Cache Tags/', $result);
		$this->assertPattern('/G\. Layout After Content And After Element With No Cache Tags/', $result);
		$this->assertNoPattern('/1\. layout before content/', $result);
		$this->assertNoPattern('/2\. in plain element/', $result);
		$this->assertNoPattern('/3\. layout after test element/', $result);
		$this->assertNoPattern('/4\. in view file/', $result);
		$this->assertNoPattern('/5\. layout after content/', $result);
		//$this->assertNoPattern('/6\. in element with no cache tags/', $result);
		$this->assertNoPattern('/7\. layout after content and after element with no cache tags/', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_complex.php';
		$this->assertTrue(file_exists($filename));
		$contents = file_get_contents($filename);
		@unlink($filename);

		$this->assertPattern('/A\. Layout Before Content/', $contents);
		$this->assertNoPattern('/B\. In Plain Element/', $contents);
		$this->assertPattern('/C\. Layout After Test Element/', $contents);
		$this->assertPattern('/D\. In View File/', $contents);
		$this->assertPattern('/E\. Layout After Content/', $contents);
		//$this->assertPattern('/F\. In Element With No Cache Tags/', $contents);
		$this->assertPattern('/G\. Layout After Content And After Element With No Cache Tags/', $contents);
		$this->assertPattern('/1\. layout before content/', $contents);
		$this->assertNoPattern('/2\. in plain element/', $contents);
		$this->assertPattern('/3\. layout after test element/', $contents);
		$this->assertPattern('/4\. in view file/', $contents);
		$this->assertPattern('/5\. layout after content/', $contents);
		//$this->assertPattern('/6\. in element with no cache tags/', $contents);
		$this->assertPattern('/7\. layout after content and after element with no cache tags/', $contents);
	}

/**
 * test cache of view vars
 *
 * @access public
 * @return void
 */
	public function testCacheViewVars() {
		$this->Controller->cache_parsing();
		$this->Controller->params = array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		);
		$this->Controller->cacheAction = 21600;
		$this->Controller->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('index');
		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertPattern('/\$this\-\>viewVars/', $contents);
		$this->assertPattern('/extract\(\$this\-\>viewVars, EXTR_SKIP\);/', $contents);
		$this->assertPattern('/php echo \$variable/', $contents);

		@unlink($filename);
	}

/**
 * test cacheAction set to a boolean
 *
 * @return void
 */
	public function testCacheActionArray() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		));
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);
		$this->Controller->request->here = '/cache_test/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('index');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cache_test_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		@unlink($filename);


		$this->Controller->cache_parsing();
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('index');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		@unlink($filename);


		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		));
		$this->Controller->cacheAction = array(
			'some_other_action' => 21600
		);
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('index');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';
		$this->assertFalse(file_exists($filename));
	}

/**
 * test with named and pass args.
 *
 * @return void
 */
	public function testCacheWithNamedAndPassedArgs() {
		Router::reload();

		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(1, 2),
			'named' => array(
				'name' => 'mark',
				'ice' => 'cream'
			)
		));
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);
		$this->Controller->request->here = '/cache_test/cache_parsing/1/2/name:mark/ice:cream';

		$View = new View($this->Controller);
		$result = $View->render('index');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cache_test_cache_parsing_1_2_name_mark_ice_cream.php';
		$this->assertTrue(file_exists($filename));
		@unlink($filename);
	}

/**
 * test that custom routes are respected when generating cache files.
 *
 * @return void
 */
	public function testCacheWithCustomRoutes() {
		Router::reload();
		Router::connect('/:lang/:controller/:action/*', array(), array('lang' => '[a-z]{3}'));

		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'lang' => 'en',
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		));
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);
		$this->Controller->request->here = '/en/cache_test/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = new View($this->Controller);
		$result = $View->render('index');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'en_cache_test_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		@unlink($filename);
	}

/**
 * test ControllerName contains AppName
 *
 * This test verifys view cache is created correctly when the app name is contained in part of the controller name.
 * (webapp Name) base name is 'cache' controller is 'cacheTest' action is 'cache_name'
 * apps url would look somehing like http://localhost/cache/cacheTest/cache_name
 *
 * @return void
 **/
	public function testCacheBaseNameControllerName() {
		$this->Controller->cache_parsing();
		$this->Controller->cacheAction = array(
			'cache_name' => 21600
		);
		$this->Controller->params = array(
			'controller' => 'cacheTest',
			'action' => 'cache_name',
			'url' => array(),
			'pass' => array(),
			'named' => array()
		);
		$this->Controller->here = '/cache/cacheTest/cache_name';
		$this->Controller->action = 'cache_name';
		$this->Controller->base = '/cache';

		$View = new View($this->Controller);
		$result = $View->render('index');

		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cache_cachetest_cache_name.php';
		$this->assertTrue(file_exists($filename));
		@unlink($filename);
    }

/**
 * test that afterRender checks the conditions correctly.
 *
 * @return void
 */
	public function testAfterRenderConditions() {
		Configure::write('Cache.check', true);
		$View = new View($this->Controller);
		$View->cacheAction = '+1 day';
		$View->output = 'test';

		$Cache = $this->getMock('CacheHelper', array('cache'), array($View));
		$Cache->expects($this->once())->method('cache')
			->with('posts/index', $View->output, false);
		$Cache->afterRender('posts/index');

		Configure::write('Cache.check', false);
		$Cache->afterRender('posts/index');

		Configure::write('Cache.check', true);
		$View->cacheAction = false;
		$Cache->afterRender('posts/index');
	}

/**
 * test that afterRender checks the conditions correctly.
 *
 * @return void
 */
	public function testAfterLayoutConditions() {
		Configure::write('Cache.check', true);
		$View = new View($this->Controller);
		$View->cacheAction = '+1 day';
		$View->output = 'test';

		$Cache = $this->getMock('CacheHelper', array('cache'), array($View));
		$Cache->expects($this->once())->method('cache')
			->with('posts/index', $View->output, true);
		$Cache->afterLayout('posts/index');

		Configure::write('Cache.check', false);
		$Cache->afterLayout('posts/index');

		Configure::write('Cache.check', true);
		$View->cacheAction = false;
		$Cache->afterLayout('posts/index');
	}

/**
 * testCacheEmptySections method
 *
 * This test must be uncommented/fixed in next release (1.2+)
 *
 * @return void
 * @access public
 *
	public function testCacheEmptySections () {
		$this->Controller->cache_parsing();
		$this->Controller->cacheAction = array('cacheTest' => 21600);
		$this->Controller->here = '/cacheTest/cache_empty_sections';
		$this->Controller->action = 'cache_empty_sections';
		$this->Controller->layout = 'cache_empty_sections';
		$this->Controller->viewPath = 'Posts';

		$View = new View($this->Controller);
		$result = $View->render('cache_empty_sections');
		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);
		$this->assertPattern(
			'@</title>\s*</head>\s*' .
			'<body>\s*' .
			'View Content\s*' .
			'cached count is: 3\s*' .
			'</body>@', $result);

		$filename = CACHE . 'views' . DS . 'cachetest_cache_empty_sections.php';
		$this->assertTrue(file_exists($filename));
		$contents = file_get_contents($filename);
		$this->assertNoPattern('/cake:nocache/', $contents);
		$this->assertPattern(
			'@<head>\s*<title>Posts</title>\s*' .
			"<\?php \$x = 1; \?>\s*" .
			'</head>\s*' .
			'<body>\s*' .
			"<\?php \$x\+\+; \?>\s*" .
			"<\?php \$x\+\+; \?>\s*" .
			'View Content\s*' .
			"<\?php \$y = 1; \?>\s*" .
			"<\?php echo 'cached count is:' . \$x; \?>\s*" .
			'@', $contents);
		@unlink($filename);
	}
*/
}
