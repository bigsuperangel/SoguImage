<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为自带 Markdown 编辑器增加系统剪贴板中的图片粘贴上传的支持，类似简书写文章页面的编辑框
 *
 * @package Sougu Image
 * @author qing
 * @version 1.0.0
 * @link https://github.com/zgq354/SoguImage
 */
class SoguImage_Plugin implements Typecho_Plugin_Interface
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
        // 在编辑文章和编辑页面的底部注入代码
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('SoguImage_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('SoguImage_Plugin', 'render');
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
    public static function config(Typecho_Widget_Helper_Form $form){}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {

        Typecho_Widget::widget('Widget_Options')->to($options);
        ?>
<script>
// 粘贴文件上传
$(document).ready(function () {
    // 上传URL
    var uploadUrl = 'https://api.oioweb.cn/api/sougou.php';

    // 上传文件函数
    function uploadFile(file) {
        // 生成一段随机的字符串作为 key
        var index = Math.random().toString(10).substr(2, 5) + '-' + Math.random().toString(36).substr(2);
        // 默认文件后缀是 png，在Chrome浏览器中剪贴板粘贴的图片都是png格式，其他浏览器暂未测试
        var fileName = index + '.png';

        // 上传时候提示的文字
        var uploadingText = '[图片上传中...(' + index + ')]';

        // 先把这段文字插入
        var textarea = $('#text'), sel = textarea.getSelection(),
        offset = (sel ? sel.start : 0) + uploadingText.length;
        textarea.replaceSelection(uploadingText);
        // 设置光标位置
        textarea.setSelection(offset, offset);

        // 设置附件栏信息
        // 先切到附件栏
        $('#tab-files-btn').click();

        // 更新附件的上传提示
        var fileInfo = {
            id: index,
            name: fileName
        }
        fileUploadStart(fileInfo);

        // 是时候展示真正的上传了
        var formData = new FormData();
        formData.append('name', fileName);
        formData.append('file', file, fileName);

        $.ajax({
            method: 'post',
            url: uploadUrl,
            data: formData,
            contentType: false,
            processData: false,
            success: function (data) {
				if(data.code==1){
					var url = data.picurl;
					textarea.val(textarea.val().replace(uploadingText, '![' + index + '](' + url + ')'));
					// 触发输入框更新事件，把状态压人栈中，解决预览不更新的问题
					textarea.trigger('paste');
				}else {
					textarea.val(textarea.val().replace(uploadingText, '[图片上传错误...]\n'));
					// 触发输入框更新事件，把状态压人栈中，解决预览不更新的问题
					textarea.trigger('paste');
					// 附件上传的 UI 更新
					fileUploadError(fileInfo);
				}
               
            },
            error: function (error) {
                textarea.val(textarea.val().replace(uploadingText, '[图片上传错误...]\n'));
                // 触发输入框更新事件，把状态压人栈中，解决预览不更新的问题
                textarea.trigger('paste');
                // 附件上传的 UI 更新
                fileUploadError(fileInfo);
            }
        });
    }

    // 监听输入框粘贴事件
    document.getElementById('text').addEventListener('paste', function (e) {
      var clipboardData = e.clipboardData;
      var items = clipboardData.items;
      for (var i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].type.match(/^image/)) {
          // 取消默认的粘贴操作
          e.preventDefault();
          // 上传文件
          uploadFile(items[i].getAsFile());
          break;
        }
      }
    });

    // 开始上传文件的提示
    function fileUploadStart (file) {
        $('<li id="' + file.id + '" class="loading">'
            + file.name + '</li>').appendTo('#file-list');
    }

    // 错误处理，相比原来的函数，做了一些微小的改造
    function fileUploadError (file) {
        var word;

        word = '<?php _e('上传出现错误'); ?>';

        var fileError = '<?php _e('%s 上传失败'); ?>'.replace('%s', file.name),
            li, exist = $('#' + file.id);

        if (exist.length > 0) {
            li = exist.removeClass('loading').html(fileError);
        } else {
            li = $('<li>' + fileError + '<br />' + word + '</li>').appendTo('#file-list');
        }

        li.effect('highlight', {color : '#FBC2C4'}, 2000, function () {
            $(this).remove();
        });
    }
})
</script>
<?php
    }
}
