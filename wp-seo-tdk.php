<?php
/*
Plugin Name: SEO TDK
Plugin URI: http://www.utubon.com/?p=1612
Description: 为您提供一个通用的网页标题、关键词、描述方案，实现最基本的SEO TDK目的。
Version: 1.0
Author: 否子戈
Author URI: http://www.utubon.com/
*/

// 创建一个函数，用以清除描述文本中的换行、HTML标签等干扰信息，只留下文本
if(!function_exists('wp_seo_tdk_clear_html_code')) :
function wp_seo_tdk_clear_html_code($string){
    $string=str_replace("\r\n",'',$string);//清除换行符
    $string=str_replace("\n",'',$string);//清除换行符
    $string=str_replace("\t",'',$string);//清除制表符
    $pattern=array("/> *([^ ]*) *</","/[\s]+/","/<!--[^!]*-->/","/\" /","/ \"/","'/\*[^*]*\*/'","/\[(.*)\]/");
    $replace=array(">\\1<"," ","","\"","\"","","");
    return preg_replace($pattern,$replace,$string);
}
endif;

// 优化网页标题，对wp_title的输出格式进行变化
add_filter('wp_title','wp_seo_tdk_title',100);
function wp_seo_tdk_title($title){
	global $page,$paged,$post;

	$title = trim($title);
	$seo_slip = __(get_option('seo_tdk_blog_slip'));
	$blog_seo_title = __(get_option('seo_tdk_blog_title'));
	$cat_seo_title_type = get_option('seo_tdk_cat_title_type');
	$post_seo_title_type = get_option('seo_tdk_post_title_type');
	if(!trim($seo_slip)){
		$seo_slip = '_';
	}
	// 首页标题优化
	if(is_home() || is_front_page()){
		if($blog_seo_title){
			$title = wp_seo_tdk_clear_html_code($blog_seo_title);
		}else{
			$title = get_bloginfo('name').$seo_slip.get_bloginfo('description');
		}
	}
	// 分类页标题
	elseif(is_category()){
		global $cat;
		$cat_id = is_object($cat)?$cat->cat_ID:$cat;
		$cat_title = single_cat_title('',false);
		$cat_seo_title = __(wp_seo_tdk_get_term_meta($cat_id,'seo_title'));
		$title = $cat_seo_title ? $cat_seo_title : $title;
		if($cat_seo_title_type == 2){
			$category = get_category($cat_id);
			while($category->parent){
				$category = get_category($category->parent);
				$title .= $seo_slip.$category->cat_name;
			}
		}
		$title .= $seo_slip.get_bloginfo('name');
	}
	elseif(is_tag()){
		global $wp_query;
		$tag_id = $wp_query->queried_object->term_id;
		$tag_name = $wp_query->queried_object->name;
		$tag_seo_title = __(wp_seo_tdk_get_term_meta($tag_id,'seo_title'));
		$title = $tag_seo_title?$tag_seo_title:$tag_name;
		$title .= $seo_slip.get_bloginfo('name');
	}
	// 文章页的标题
	elseif(is_singular()){
		$title = __($post->post_title?$post->post_title:$post->post_date);
		if($post_seo_title_type == 2){
			$category = get_the_category();
			$category = $category[0];
			while($category->cat_ID){
				$title .= $seo_slip.$category->cat_name;
				$category = get_category($category->parent);
			}
		}
		$title .= $seo_slip.get_bloginfo('name');
	}
	elseif(is_feed()){
		return $title;
	}
	// 其他情况
	else{
		$title .= $seo_slip.get_bloginfo('name');
	}
	if($paged >= 2 || $page >= 2){
		$title .= $seo_slip.sprintf(__('第%s页'),max($paged,$page));
	}
	$title = wp_seo_tdk_clear_html_code($title);
	return $title;
}
// 网页关键字描述
function wp_seo_tdk_keywords(){

	// 为了避免翻页带来的问题，把翻页以后的给屏蔽掉
	if(is_paged())return;
	
	$keywords = '';
	if(is_home() || is_front_page()){
		$keywords = __(get_option('seo_tdk_blog_keywords'));
	}
	elseif(is_category()){
		global $cat;
		$cat_id = is_object($cat)?$cat->cat_ID:$cat;
		$cat_keywords = __(wp_seo_tdk_get_term_meta($cat_id,'seo_keywords'));
		if($cat_keywords){
			$keywords = $cat_keywords;
		}else{
			$keywords = single_cat_title('',false);
		}
	}
	elseif(is_tag()){
		global $wp_query;
		$tag_id = $wp_query->queried_object->term_id;
		$tag_keywords = __(wp_seo_tdk_get_term_meta($tag_id,'seo_keywords'));
		if($tag_keywords){
			$keywords = $tag_keywords;
		}else{
			$keywords = $wp_query->queried_object->name;
		}
	}
	elseif(is_singular()){
		global $post;
		// 第一种是使用标签
		$tags = strip_tags(get_the_tag_list('',',',''));
		// 第二种是使用自定义域的keywords
		$metakeywords = __(trim(stripslashes(strip_tags(get_post_meta($post->ID,'keywords',true)))));
		// 第三种是使用分类名称
		$cats = '';
		$categories = get_the_category();
		foreach($categories as $category){
			$cats .= ','.$category->cat_name;
		}
		// 当存在标签时，使用标签；当存在自定义keywords时，把它附加到标签上，如果没有标签，就使用自定义的keywords；如果这两者都不存在，就使用分类名称
		if($tags && $metakeywords){
			$keywords = $tags.','.$metakeywords;
		}elseif($tags && !$metakeywords){
			$keywords = $tags;
		}elseif(!$tags && $metakeywords){
			$keywords = $metakeywords;
		}else{
			$keywords = $post->post_title;
		}
		$keywords .= $cats;
		$keywords = trim(str_replace('"','',$keywords));
		$keywords = wp_seo_tdk_clear_html_code($keywords);
	}
	if($keywords)echo '<meta name="keywords" content="'.$keywords.'" />'."\n";
}
// 网页描述
function wp_seo_tdk_description(){
	
	// 为了避免翻页带来的问题，把翻页以后的给屏蔽掉
	if(is_paged())return;

	$description = '';
	if(is_home() || is_front_page()){
		$description = __(strip_tags(get_option('seo_tdk_blog_description')));
	}
	elseif(is_category()){
		$description = __(strip_tags(category_description()));
	}
	elseif(is_tag()){
		$description = __(strip_tags(tag_description()));
	}
	elseif(is_singular()){
		global $post;
		// 第一种是使用自定义域的
		$description = __(strip_tags(get_post_meta($post->ID,'description',true)));
		// 第二种是使用摘要
		$excerpt = __(strip_tags($post->post_excerpt));
		// 第三种是使用文章的前200字
		$content = mb_strimwidth(__(strip_tags($post->post_content)),0,300,'...');
		// 将三者结合起来
		if($description == '')$description = $excerpt;
		if($description == '')$description = $content;
		$description = str_replace('"','',$description);
		$description = wp_seo_tdk_clear_html_code(trim($description));
	}
	if($description)echo '<meta name="description" content="'.$description.'" />'."\n";
}
// 将关键词和描述输出在wp_head区域
add_action('wp_head','add_wp_head_action_seo_tdk',0);
function add_wp_head_action_seo_tdk(){
	wp_seo_tdk_keywords();
	wp_seo_tdk_description();
}

// 创建后台设置页面
add_action('admin_menu','add_admin_options_submenu_seo_tdk');
function add_admin_options_submenu_seo_tdk(){
	add_plugins_page('SEO Title Description Keywords Option','SEO TDK','edit_theme_options','seo_tdk','wp_seo_tdk_admin_setting');
}
add_action('admin_init','update_admin_options_submenu_seo_tdk');
function update_admin_options_submenu_seo_tdk(){
	if(!empty($_POST) && $_POST['page'] == $_GET['page'] && $_POST['action'] == 'update_seo_tdk'){
		check_admin_referer();
		update_option('seo_tdk_blog_slip',$_POST['seo_tdk_blog_slip']);
		update_option('seo_tdk_blog_title',$_POST['seo_tdk_blog_title']);
		update_option('seo_tdk_blog_keywords',$_POST['seo_tdk_blog_keywords']);
		update_option('seo_tdk_blog_description',$_POST['seo_tdk_blog_description']);
		update_option('seo_tdk_cat_title_type',$_POST['seo_tdk_cat_title_type']);
		update_option('seo_tdk_post_title_type',$_POST['seo_tdk_post_title_type']);
		wp_redirect(admin_url('admin.php?page='.$_POST['page'].'&saved=true&time='.time()));
		exit;
	}
}
function wp_seo_tdk_admin_setting(){
	if(@$_GET['saved'] == 'true')echo '<div id="message" class="updated fade"><p><strong>更新成功！</strong></p></div>';
	$seo_tdk_blog_slip = get_option('seo_tdk_blog_slip');
	$seo_tdk_blog_title = get_option('seo_tdk_blog_title');
	$seo_tdk_blog_keywords = get_option('seo_tdk_blog_keywords');
	$seo_tdk_blog_description = get_option('seo_tdk_blog_description');
	$seo_tdk_cat_title_type = get_option('seo_tdk_cat_title_type');
	$seo_tdk_post_title_type = get_option('seo_tdk_post_title_type');
?>
<div class="wrap" id="seo-tdk-admin">
	<h2>SEO Ttitle（标题） Description（网页描述） Keywords（网页关键词）设置</h2>
	<br class="clear" />
    <div class="metabox-holder">
	    <form method="post">
			<div class="postbox">
				<h3>全局设置</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<p>间隔符：<input type="text" class="regular-text" style="width:60px;" name="seo_tdk_blog_slip" value="<?php echo $seo_tdk_blog_slip; ?>" /><br />
					一般来说都从"-"、"_"、"|"中进行选择。注意，下面的演示中有的时候用_，有的时候用-，但实际效果还是根据这里的设置而定。</p>
				</div>
			</div>
			<div class="postbox">
				<h3>首页（blog）设置</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<p>标题：<input type="text" class="regular-text" name="seo_tdk_blog_title" value="<?php echo $seo_tdk_blog_title; ?>" /></p>
					<p>关键词：<input type="text" class="regular-text" name="seo_tdk_blog_keywords" value="<?php echo $seo_tdk_blog_keywords; ?>" /></p>
					<p>网页描述：<br>
					<textarea class="large-text" name="seo_tdk_blog_description"><?php echo $seo_tdk_blog_description; ?></textarea></p>
				</div>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<p>由于首页没有独立的突出信息，因此，需要通过这里进行单独设置。</p>
				</div>
			</div>
			<div class="postbox">
				<h3>分类（category）标签（tag）页设置</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<p>标题格式：<select name="seo_tdk_cat_title_type">
						<option value="1" <?php selected($seo_tdk_cat_title_type,1); ?>>分类名-博客名</option>
						<option value="2" <?php selected($seo_tdk_cat_title_type,2); ?>>分类名-父分类-博客名</option>
					</select></p>
				</div>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<p>一般情况下，wp_title都会直接打印出分类名作为分类页的标题，本功能允许你设置自己的分类页标题。在编辑具体的分类页时可以看到category_meta字段，你可以填写对应的值。分类页的描述将直接采用分类描述。<br>
					注意：这些meta值应该与你在这里填写的标题格式进行统筹规划。</p>
					<p>例如，您在原本为“帆布鞋”的分类中填写了新的标题字段为“帆布鞋 凡客诚品”，那么在页面中将使用后面的代替前面的，如你的标题将变为“帆布鞋 凡客诚品-父分类-根分类-博客名”</p>
					<p>分类页的关键词由category_meta_keywords确定，如果不填写，直接使用分类名。</p>
					<p>分类页的描述由分类的描述确定。</p>
					<p>因为标签没有父标签之说，所以这里的设置对标签无效。</p>
				</div>
			</div>
			<div class="postbox">
				<h3>文章（post）页设置</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<p>标题格式：<select name="seo_tdk_post_title_type">
						<option value="1" <?php selected($seo_tdk_post_title_type,1); ?>>文章名-博客名</option>
						<option value="2" <?php selected($seo_tdk_post_title_type,2); ?>>文章名-分类层级-博客名</option>
					</select></p>
				</div>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<p>文章页的重要性不必多说了吧！</p>
					<p>文章页的关键词：首先使用文章标签作为关键词，接着你自己创建的keywords自定义栏目的值作为关键词，再接着使用文章名和分类名称作为关键词。本插件会同时使用它们，无论缺少谁都不会直接影响关键词的使用。</p>
					<p>文章页的描述：首先使用文章自定义栏目description的值作为描述，如果没有的话，使用填写的文章摘要作为描述，如果还没有的话，摘取文章开头的150个字作为描述。注意，它们之间的选择是有先后顺序的，如果你同时填写了description自定义栏目和摘要，只会选择自定义栏目的值作为摘要。记住，你的文章开头150个字也很重要。</p>
				</div>
			</div>
			<div class="postbox">
				<h3>说明</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					</p>本插件为付费插件，请为了保证您自己的权益，不要给他人使用。<br>
					如果您需要更为高级的功能（如对标签页进行SEO等），请联系否子戈(frustigor@163.com)进行开发。<br>
					<a href="http://sighttp.qq.com/authd?IDKEY=b081e2503835998097637694e8abde64dc6027296798242d" target="_blank" title="点击这里和否子戈（frustigor）在线交流"><img border="0"  src="http://wpa.qq.com/imgd?IDKEY=b081e2503835998097637694e8abde64dc6027296798242d&pic=3"/></a></p>
					<p>向插件作者捐赠：<a href="http://me.alipay.com/tangshuang" target="_blank">支付宝</a>、BTC（164jDbmE8ncUYbnuLvUzurXKfw9L7aTLGD）、PPC（PNijEw4YyrWL9DLorGD46AGbRbXHrtfQHx）、XPM（AbDGH5B7zFnKgMJM8ujV3br3R2V31qrF2F）</p>
				</div>
			</div>
			<p class="submit">
				<button type="submit" class="button-primary">提交</button>
			</p>
		    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
			<input type="hidden" name="action" value="update_seo_tdk" />
		    <?php wp_nonce_field(); ?>
	    </form>
    </div>
</div>
<?php
}

/**
下方的代码用以实现term_meta
**/

add_action('category_add_form_fields','wp_seo_tdk_extra_term_fields');
add_action('edit_category_form_fields','wp_seo_tdk_extra_term_fields');
add_action('add_tag_form_fields','wp_seo_tdk_extra_term_fields');
add_action('edit_tag_form_fields','wp_seo_tdk_extra_term_fields');
function wp_seo_tdk_extra_term_fields($term){
    $term_id = $term->term_id;
    $term_seo_title = get_option("term_{$term_id}_meta_seo_title");
    $term_seo_keywords = get_option("term_{$term_id}_meta_seo_keywords");
?>
<tr class="form-field">
	<th scope="row" valign="top"><label for="term_seo_title">SEO标题</label></th>
	<td><input type="text" name="term_meta_seo_title" id="term_seo_title" class="regular-text" value="<?php echo $term_seo_title ? $term_seo_title : ''; ?>"></td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top"><label for="term_seo_keywords">SEO关键词</label></th>
	<td><input type="text" name="term_meta_seo_keywords" id="term_seo_keywords" class="regular-text" value="<?php echo $term_seo_keywords ? $term_seo_keywords : ''; ?>"></td>
</tr>
<?php
}

add_action('created_category','wp_seo_tdk_save_extra_term_fileds');
add_action('edited_category','wp_seo_tdk_save_extra_term_fileds');
add_action('created_post_tag','wp_seo_tdk_save_extra_term_fileds');
add_action('edited_post_tag','wp_seo_tdk_save_extra_term_fileds');
function wp_seo_tdk_save_extra_term_fileds($term_id){
	update_option("term_{$term_id}_meta_seo_title",$_POST['term_meta_seo_title']);
	update_option("term_{$term_id}_meta_seo_keywords",$_POST['term_meta_seo_keywords']);
}

function wp_seo_tdk_get_term_meta($term_id,$meta_key){
    if(is_object($term_id))$term_id = $term_id->term_id;
	$term_meta = get_option("term_{$term_id}_meta_{$meta_key}");
	if($term_meta){
		return $term_meta;
	}else{
		return null;
	}
}