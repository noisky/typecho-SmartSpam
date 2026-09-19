<?php
/**
 * 智能评论过滤器，让机器人彻底远离你！
 * 
 * @package SmartSpam
 * @author 饭饭
 * @version 3.0.1
 * @link https://github.com/noisky/typecho-SmartSpam
 */

class SmartSpam_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {    
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('SmartSpam_Plugin', 'filter');
		return _t('评论过滤器启用成功，请配置需要过滤的内容');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
	{
	    $opt_visitor = new Typecho_Widget_Helper_Form_Element_Radio('opt_visitor', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "none",
			_t('游客评论'), "如果评论发布者的未登录网站以游客身份评论，将执行该操作");
        $form->addInput($opt_visitor);
	    
	    
	    
        $opt_ip = new Typecho_Widget_Helper_Form_Element_Radio('opt_ip', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('屏蔽IP操作'), "如果评论发布者的IP在屏蔽IP段，将执行该操作");
        $form->addInput($opt_ip);
        $words_ip = new Typecho_Widget_Helper_Form_Element_Textarea('words_ip', NULL, "0.0.0.0",
			_t('屏蔽IP'), _t('多条IP请用换行符隔开<br />支持用*号匹配IP段，如：192.168.*.*'));
        $form->addInput($words_ip);
         
        
        $opt_mail = new Typecho_Widget_Helper_Form_Element_Radio('opt_mail', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('屏蔽邮箱操作'), "如果评论发布者的邮箱与禁止的一致，将执行该操作");
        $form->addInput($opt_mail);
        $words_mail = new Typecho_Widget_Helper_Form_Element_Textarea('words_mail', NULL, "",
			_t('邮箱关键词'), _t('多个邮箱请用换行符隔开<br />可以是邮箱的全部，或者邮箱部分关键词'));
        $form->addInput($words_mail);        
        
        
        
        $opt_url = new Typecho_Widget_Helper_Form_Element_Radio('opt_url', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('屏蔽网址操作'), "如果评论发布者的网址与禁止的一致，将执行该操作。如果网址为空，该项不会起作用。");
        $form->addInput($opt_url);
        $words_url = new Typecho_Widget_Helper_Form_Element_Textarea('words_url', NULL, "",
			_t('网址关键词'), _t('多个网址请用换行符隔开<br />可以是网址的全部，或者网址部分关键词。如果网址为空，该项不会起作用。'));
        $form->addInput($words_url);
        
        
        $opt_title = new Typecho_Widget_Helper_Form_Element_Radio('opt_title', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('内容含有文章标题'), "如果评论内容中含有本页面的文章标题，则强行按该操作执行");
        $form->addInput($opt_title);
        
        
        
        $opt_au = new Typecho_Widget_Helper_Form_Element_Radio('opt_au', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('屏蔽昵称关键词操作'), "如果评论发布者的昵称含有该关键词，将执行该操作");
        $form->addInput($opt_au);

        $words_au = new Typecho_Widget_Helper_Form_Element_Textarea('words_au', NULL, "",
			_t('屏蔽昵称关键词'), _t('多个关键词请用换行符隔开'));
        $form->addInput($words_au);
        
        
        
        $au_length_min = new Typecho_Widget_Helper_Form_Element_Text('au_length_min', NULL, '1', '昵称最短字符数', '昵称允许的最短字符数。');
        $au_length_min->input->setAttribute('class', 'mini');
        $form->addInput($au_length_min);
        $au_length_max = new Typecho_Widget_Helper_Form_Element_Text('au_length_max', NULL, '15', '昵称最长字符数', '昵称允许的最长字符数');
        $au_length_max->input->setAttribute('class', 'mini');
        $form->addInput($au_length_max);
        $opt_au_length = new Typecho_Widget_Helper_Form_Element_Radio('opt_au_length', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('昵称字符长度操作'), "如果昵称长度不符合条件，则强行按该操作执行。如果选择[无动作]，将忽略下面长度的设置");
        $form->addInput($opt_au_length);   
        
        
        
        
        $opt_nojp_au = new Typecho_Widget_Helper_Form_Element_Radio('opt_nojp_au', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('昵称日文操作'), "如果用户昵称中包含日文，则强行按该操作执行");
        $form->addInput($opt_nojp_au);
        
        $opt_nourl_au = new Typecho_Widget_Helper_Form_Element_Radio('opt_nourl_au', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('昵称网址操作'), "如果用户昵称是网址，则强行按该操作执行");
        $form->addInput($opt_nourl_au);
        

        
        $opt_nojp = new Typecho_Widget_Helper_Form_Element_Radio('opt_nojp', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('日文评论操作'), "如果评论中包含日文，则强行按该操作执行");
        $form->addInput($opt_nojp);
        
       
        
        
        $opt_nocn = new Typecho_Widget_Helper_Form_Element_Radio('opt_nocn', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('非中文评论操作'), "如果评论中不包含中文，则强行按该操作执行");
        $form->addInput($opt_nocn);
        
        
        $length_min = new Typecho_Widget_Helper_Form_Element_Text('length_min', NULL, '5', '评论最短字符数', '允许评论的最短字符数。');
        $length_min->input->setAttribute('class', 'mini');
        $form->addInput($length_min);
        $length_max = new Typecho_Widget_Helper_Form_Element_Text('length_max', NULL, '200', '评论最长字符数', '允许评论的最长字符数');
        $length_max->input->setAttribute('class', 'mini');
        $form->addInput($length_max);
        $opt_length = new Typecho_Widget_Helper_Form_Element_Radio('opt_length', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('评论字符长度操作'), "如果评论中长度不符合条件，则强行按该操作执行。如果选择[无动作]，将忽略下面长度的设置");
        $form->addInput($opt_length);        
        

        $opt_ban = new Typecho_Widget_Helper_Form_Element_Radio('opt_ban', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('禁止词汇操作'), "如果评论中包含禁止词汇列表中的词汇，将执行该操作");
        $form->addInput($opt_ban);

        $words_ban = new Typecho_Widget_Helper_Form_Element_Textarea('words_ban', NULL, "fuck\n操你妈\n[url\n[/url]",
			_t('禁止词汇'), _t('多条词汇请用换行符隔开'));
        $form->addInput($words_ban);

        $opt_chk = new Typecho_Widget_Helper_Form_Element_Radio('opt_chk', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('敏感词汇操作'), "如果评论中包含敏感词汇列表中的词汇，将执行该操作");
        $form->addInput($opt_chk);

        $words_chk = new Typecho_Widget_Helper_Form_Element_Textarea('words_chk', NULL, "http://",
			_t('敏感词汇'), _t('多条词汇请用换行符隔开<br />注意：如果词汇同时出现于禁止词汇，则执行禁止词汇操作'));
        $form->addInput($words_chk);
	}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 评论过滤器
     * 
     */
    public static function filter($comments, $post,$last)
    {
        // 兼容其他过滤器返回空值或不完整评论数据的情况。
        $comment = !empty($last) && is_array($last)
            ? $last
            : (is_array($comments) ? $comments : array());
        $comment['cid'] = isset($comment['cid']) ? (int) $comment['cid'] : 0;
        $comment['text'] = self::stringValue(isset($comment['text']) ? $comment['text'] : '');
        $comment['ip'] = self::stringValue(isset($comment['ip']) ? $comment['ip'] : '');
        $comment['mail'] = self::stringValue(isset($comment['mail']) ? $comment['mail'] : '');
        $comment['url'] = self::stringValue(isset($comment['url']) ? $comment['url'] : '');
        $comment['author'] = self::stringValue(isset($comment['author']) ? $comment['author'] : '');

        $options = Typecho_Widget::widget('Widget_Options');
        $user = Typecho_Widget::widget('Widget_User');
		$filter_set = $options->plugin('SmartSpam');
		$optVisitor = self::actionValue($filter_set, 'opt_visitor');
		$optTitle = self::actionValue($filter_set, 'opt_title');
		$optIp = self::actionValue($filter_set, 'opt_ip');
		$optMail = self::actionValue($filter_set, 'opt_mail');
		$optUrl = self::actionValue($filter_set, 'opt_url');
		$optAuthor = self::actionValue($filter_set, 'opt_au');
		$optAuthorLength = self::actionValue($filter_set, 'opt_au_length');
		$optNoJpAuthor = self::actionValue($filter_set, 'opt_nojp_au');
		$optNoUrlAuthor = self::actionValue($filter_set, 'opt_nourl_au');
		$optNoJp = self::actionValue($filter_set, 'opt_nojp');
		$optNoCn = self::actionValue($filter_set, 'opt_nocn');
		$optLength = self::actionValue($filter_set, 'opt_length');
		$optBan = self::actionValue($filter_set, 'opt_ban');
		$optCheck = self::actionValue($filter_set, 'opt_chk');
		$wordsIp = self::stringValue(self::configValue($filter_set, 'words_ip', ''));
		$wordsMail = self::stringValue(self::configValue($filter_set, 'words_mail', ''));
		$wordsUrl = self::stringValue(self::configValue($filter_set, 'words_url', ''));
		$wordsAuthor = self::stringValue(self::configValue($filter_set, 'words_au', ''));
		$wordsBan = self::stringValue(self::configValue($filter_set, 'words_ban', ''));
		$wordsCheck = self::stringValue(self::configValue($filter_set, 'words_chk', ''));
		$authorLengthMin = self::integerValue($filter_set, 'au_length_min', 1);
		$authorLengthMax = self::integerValue($filter_set, 'au_length_max', 15);
		$lengthMin = self::integerValue($filter_set, 'length_min', 5);
		$lengthMax = self::integerValue($filter_set, 'length_max', 200);
		$opt = "none";
		$error = "";
        

		//游客进行评论进行权限处理
		if($opt === "none" && $optVisitor !== "none" && !$user->hasLogin()){
			 $error = "对不起，本站暂时禁止游客进行评论！";
			 $opt = $optVisitor;
		}

        //屏蔽评论内容包含文章标题
		if ($opt === "none" && $optTitle !== "none") {
			 $db = Typecho_Db::get();
            // 获取评论所在文章
            $po = $db->fetchRow($db->select('title')->from('table.contents')->where('cid = ?', $comment['cid']));        
			$title = is_array($po) && isset($po['title'])
				? self::stringValue($po['title'])
				: '';
			if ($title !== '' && false !== strpos($comment['text'], $title)) {
                $error = "对不起，评论内容不允许包含文章标题";
				$opt = $optTitle;
            }        
		}
        

		//屏蔽IP段处理
		if ($opt === "none" && $optIp !== "none") {
			if (SmartSpam_Plugin::check_ip($wordsIp, $comment['ip'])) {
				$error = "评论发布者的IP已被管理员屏蔽";
				$opt = $optIp;
			}			
		}       
        
        
        //屏蔽邮箱处理
		if ($opt === "none" && $optMail !== "none") {
			if (SmartSpam_Plugin::check_in($wordsMail, $comment['mail'])) {
				$error = "评论发布者的邮箱地址被管理员屏蔽";
				$opt = $optMail;
			}			
		}  
        
        //屏蔽网址处理
        if ($wordsUrl !== ''){
			if ($opt === "none" && $optUrl !== "none") {
				if (SmartSpam_Plugin::check_in($wordsUrl, $comment['url'])) {
					$error = "评论发布者的网址被管理员屏蔽";
					$opt = $optUrl;
                }			
            }
        }        
        
        
        //屏蔽昵称关键词处理
		if ($opt === "none" && $optAuthor !== "none") {
			if (SmartSpam_Plugin::check_in($wordsAuthor, $comment['author'])) {
				$error = "对不起，昵称的部分字符已经被管理员屏蔽，请更换";
				$opt = $optAuthor;
			}			
		}
        
        
        //日文评论处理
		if ($opt === "none" && $optNoJp !== "none") {
			if (preg_match("/[\x{3040}-\x{31ff}]/u", $comment['text']) > 0) {
				$error = "禁止使用日文";
				$opt = $optNoJp;
			}
		}
        
        
        //日文用户昵称处理
		if ($opt === "none" && $optNoJpAuthor !== "none") {
			if (preg_match("/[\x{3040}-\x{31ff}]/u", $comment['author']) > 0) {
				$error = "用户昵称禁止使用日文";
				$opt = $optNoJpAuthor;
			}
		}
        
        
        //昵称长度检测
		if ($opt === "none" && $optAuthorLength !== "none") {
			if(SmartSpam_Plugin::strLength($comment['author']) < $authorLengthMin){
				$error = "昵称请不得少于".$authorLengthMin."个字符";
				$opt = $optAuthorLength;
            }else 
            if(SmartSpam_Plugin::strLength($comment['author']) > $authorLengthMax){
                $error = "昵称请不得多于".$authorLengthMax."个字符";
				$opt = $optAuthorLength;
            }
             
		}
        
        //用户昵称网址判断处理
		if ($opt === "none" && $optNoUrlAuthor !== "none") {
            if (preg_match(" /^((https?|ftp|news):\/\/)?([a-z]([a-z0-9\-]*[\.。])+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)|(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))(\/[a-z0-9_\-\.~]+)*(\/([a-z0-9_\-\.]*)(\?[a-z0-9+_\-\.%=&]*)?)?(#[a-z][a-z0-9_]*)?$/ ", $comment['author']) > 0) {
				$error = "用户昵称不允许为网址";
				$opt = $optNoUrlAuthor;
			}
		}
            
        
		//纯中文评论处理
		if ($opt === "none" && $optNoCn !== "none") {
			if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $comment['text']) == 0) {
				$error = "评论内容请不少于一个中文汉字";
				$opt = $optNoCn;
			}
		}
        
        
        //字符长度检测
		if ($opt === "none" && $optLength !== "none") {
			if(SmartSpam_Plugin::strLength($comment['text']) < $lengthMin){
                $error = "评论内容请不得少于".$lengthMin."个字符";
				$opt = $optLength;
            }else 
            if(SmartSpam_Plugin::strLength($comment['text']) > $lengthMax){
                $error = "评论内容请不得多于".$lengthMax."个字符";
				$opt = $optLength;
            }
             
		}
        
		//检查禁止词汇
		if ($opt === "none" && $optBan !== "none") {
			if (SmartSpam_Plugin::check_in($wordsBan, $comment['text'])) {
				$error = "评论内容中包含禁止词汇";
				$opt = $optBan;
			}
		}
		//检查敏感词汇
		if ($opt === "none" && $optCheck !== "none") {
			if (SmartSpam_Plugin::check_in($wordsCheck, $comment['text'])) {
				$error = "评论内容中包含敏感词汇";
				$opt = $optCheck;
			}
		}



		//执行操作
		if ($opt === "abandon") {
			Typecho_Cookie::set('__typecho_remember_text', $comment['text']);

            /**
             * 所有设置为“评论失败”的过滤规则共用此可选回显协议。
             *
             * 未声明协议时保持原有异常行为，兼容未接入该协议的主题。
             * 协议只改变错误展示方式，不改变评论过滤结果。
             */
            $protocol = isset($_POST['comment_error_protocol'])
                && is_string($_POST['comment_error_protocol'])
                ? trim($_POST['comment_error_protocol'])
                : '';
            if ('prg-v1' === $protocol) {
                Typecho_Cookie::set(
                    '__typecho_comment_error',
                    '' !== $error ? $error : '评论提交失败'
                );

                $anchor = null;
                if (isset($_POST['comment_error_anchor'])
                    && is_string($_POST['comment_error_anchor'])) {
                    $anchorValue = trim($_POST['comment_error_anchor']);
                    if (preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $anchorValue)) {
                        $anchor = '#' . $anchorValue;
                    }
                }

            $default = is_object($post) && !empty($post->permalink)
                ? $post->permalink
                : '/';
            Typecho_Widget::widget('Widget_Options')->response->goBack($anchor, $default);
            }

            throw new Typecho_Widget_Exception($error);
		}
		else if ($opt === "spam") {
			$comment['status'] = 'spam';
		}
		else if ($opt === "waiting") {
			$comment['status'] = 'waiting';
		}
		Typecho_Cookie::delete('__typecho_remember_text');
        return $comment;
    }
    
    /**
    * PHP获取字符串中英文混合长度 
    */
    private static function strLength($str)
    {
        $str = self::stringValue($str);
        if ($str === '') {
            return 0;
        }

        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($str, 'UTF-8');
        }

        $matched = preg_match_all('/./us', $str, $match);
        return false === $matched ? 0 : (int) $matched;
    }
        

    /**
     * 检查$str中是否含有$words_str中的词汇
     * 
     */
	private static function check_in($words_str, $str)
	{
        // 如果未设置屏蔽词，就不检测，直接通过
		$words_str = is_string($words_str) ? trim($words_str) : '';
		$str = self::stringValue($str);
		if ($words_str === '' || $str === '') return false;

		$words = explode("\n", $words_str);
		if (empty($words)) {
			return false;
		}
		foreach ($words as $word) {
            $word = trim($word);
            if ($word !== '' && false !== strpos($str, $word)) {
                return true;
            }
		}
		return false;
	}

    /**
     * 检查$ip中是否在$words_ip的IP段中
     * 
     */
	private static function check_ip($words_ip, $ip)
	{
		$words_ip = is_string($words_ip) ? trim($words_ip) : '';
		$ip = trim(self::stringValue($ip));
		if ($words_ip === '' || $ip === '') {
			return false;
		}

		$words = explode("\n", $words_ip);
		foreach ($words as $word) {
			$word = trim($word);
			if ($word === '') {
				continue;
			}
			if (false !== strpos($word, '*')) {
				$pattern = '/^' . str_replace('\\*', '[0-9]{1,3}', preg_quote($word, '/')) . '$/';
				if (preg_match($pattern, $ip) === 1) {
					return true;
				}
			} else {
				if ($ip === $word) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 读取配置项，不让缺失配置阻断评论处理。
	 */
	private static function configValue($config, $name, $default)
	{
		if (is_array($config) && array_key_exists($name, $config)) {
			return $config[$name];
		}

		if ($config instanceof ArrayAccess && isset($config[$name])) {
			return $config[$name];
		}

		if (is_object($config) && property_exists($config, $name)) {
			return $config->{$name};
		}

		return $default;
	}

	/**
	 * 将外部值安全转换为字符串。
	 */
	private static function stringValue($value)
	{
		return is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');
	}

	/**
	 * 读取合法的过滤动作，避免异常配置产生不可预期行为。
	 */
	private static function actionValue($config, $name)
	{
		$action = self::stringValue(self::configValue($config, $name, 'none'));
		$actions = array('none', 'waiting', 'spam', 'abandon');

		return in_array($action, $actions, true) ? $action : 'none';
	}

	/**
	 * 读取整数配置，并在配置缺失或格式错误时使用默认值。
	 */
	private static function integerValue($config, $name, $default)
	{
		$value = self::configValue($config, $name, $default);

		return is_numeric($value) ? (int) $value : $default;
	}
}
