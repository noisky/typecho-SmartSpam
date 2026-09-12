# SmartSpam

SmartSpam 是一个 Typecho 评论过滤插件，用于根据评论者信息、评论内容和配置规则拦截或标记评论。

## 功能

支持检测以下内容：

- 游客评论、文章标题；
- 评论者 IP、邮箱、网址和昵称关键词；
- 昵称和评论内容长度；
- 昵称或评论内容中的日文、非中文内容；
- 禁止词和敏感词。

规则按照插件过滤器中的顺序执行，同一条评论命中规则后，后续规则不会覆盖当前结果。

## 安装

1. 将 `SmartSpam` 目录复制到 Typecho 的 `usr/plugins/` 目录；
2. 在 Typecho 后台进入“控制台 → 插件”；
3. 启用 SmartSpam，并进入设置页面配置检测规则。

插件通过 Typecho 的评论过滤器工作，不修改 Typecho 核心。

验证码、Typecho 原生校验和其他评论过滤插件仍按各自的过滤器顺序执行。

## 处理动作

每个检测项目都可以选择以下动作：

| 界面选项 | 内部值 | 行为 |
| --- | --- | --- |
| 无动作 | `none` | 忽略检测结果，继续处理评论 |
| 标记为待审核 | `waiting` | 评论写入数据库，状态为待审核 |
| 标记为垃圾 | `spam` | 评论写入数据库，状态为垃圾 |
| 评论失败 | `abandon` | 拒绝评论并显示错误提示或抛出异常 |

“评论失败”表示拒绝当前评论，不会写入评论记录。

未声明错误回退协议时，SmartSpam 保持原有行为，由 Typecho 显示 500 错误页面；

声明协议的主题可以将错误回退到评论表单。

## 评论失败提示

以下提示由 SmartSpam 生成，对应检测项目设置为“评论失败”时使用：

| 触发条件 | 默认提示 |
| --- | --- |
| 游客评论被禁止 | 对不起，本站暂时禁止游客进行评论！ |
| 评论包含文章标题 | 对不起，评论内容不允许包含文章标题 |
| IP 命中屏蔽列表 | 评论发布者的IP已被管理员屏蔽 |
| 邮箱命中屏蔽列表 | 评论发布者的邮箱地址被管理员屏蔽 |
| 网址命中屏蔽列表 | 评论发布者的网址被管理员屏蔽 |
| 昵称命中关键词 | 对不起，昵称的部分字符已经被管理员屏蔽，请更换 |
| 评论包含日文 | 禁止使用日文 |
| 昵称包含日文 | 用户昵称禁止使用日文 |
| 昵称长度过短 | 昵称请不得少于配置的最小字符数 |
| 昵称长度过长 | 昵称请不得多于配置的最大字符数 |
| 昵称为网址 | 用户昵称不允许为网址 |
| 评论不包含中文 | 评论内容请不少于一个中文汉字 |
| 评论长度过短 | 评论内容请不得少于配置的最小字符数 |
| 评论长度过长 | 评论内容请不得多于配置的最大字符数 |
| 命中禁止词 | 评论内容中包含禁止词汇 |
| 命中敏感词 | 评论内容中包含敏感词汇 |

## 自定义主题接入评论错误回退协议

SmartSpam 默认通过 Typecho 异常机制处理“评论失败”，未接入协议的主题仍显示 500 页面。自定义主题接入可选的 `prg-v1` 协议后，可以在不修改 Typecho 核心、不使用 AJAX 的情况下返回评论表单并显示错误。

### 如何接入

#### 1. 声明协议

将以下字段放入评论表单，`comment_error_anchor` 对应表单的 `id`：

```html
<input type="hidden" name="comment_error_protocol" value="prg-v1">
<input type="hidden" name="comment_error_anchor" value="comment_form">
```

`comment_error_protocol=prg-v1` 启用协议，`comment_error_anchor` 用于回退后的片段定位。

#### 2. 显示错误

读取一次性的 `__typecho_comment_error`，转义显示后删除 Cookie：

```php
<?php
$commentError = Typecho_Cookie::get('__typecho_comment_error', '');
Typecho_Cookie::delete('__typecho_comment_error');
?>
<?php if ('' !== $commentError): ?>
    <div class="alert alert-danger comment-error" role="alert">
        <?php echo htmlspecialchars($commentError, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>
```

#### 3. 保留内容恢复

保留主题原有的 `__typecho_remember_text` 恢复逻辑。主题不需要调用 `SmartSpam_Plugin` 或判断主题名称。

### Material 主题参考

Material 已实现该协议，自定义主题可以参考其接入方式：

[https://github.com/noisky/typecho_material_theme](https://github.com/noisky/typecho_material_theme)

协议只改变错误展示方式，不改变过滤结果。Geetest 继续使用自己的错误回退逻辑。

## 兼容性

- 未声明 `prg-v1` 的主题继续显示原有 500 页面；
- `waiting` 和 `spam` 仍按原逻辑保存评论，不触发失败提示；
- Geetest 使用自己的字段和错误回退逻辑，SmartSpam 不读取其 Cookie；
- Typecho 核心校验或其他未接入协议的插件异常不在本协议范围内。

## 版本记录

- 3.0.0（2026-09-12）：增加可选的通用评论错误回退协议，支持自定义主题在保留原生表单提交的前提下回显评论失败提示；
- 2.8.2（2024-09-19）：解决高版本 PHP 8.2 下 `strpos()` 传入 `null` 的弃用问题；
- 2.8.1（2023-12-16）：修复屏蔽 IP 或禁止词列表出现空行时导致所有内容被误判的问题；
- 2.8.0（2023-12-16）：修复未设置某项拦截动作时导致该项审核无法通过的问题；
- 2.7.0（2021-03-08）：增加游客评论处理，并提升同接口插件间的兼容性；
- 2.6.0（2014-10-18）：增加网址检测；
- 2.5.0（2014-08-30）：增加文章标题检测；
- 2.4.0（2014-08-27）：增加昵称关键词和邮箱检测；
- 2.3.0（2013-12-18）：增加昵称长度、昵称日文和网址判断；
- 2.2.1（2013-12-04）：修改插件启用时的默认选项为“评论失败”；
- 2.2.0（2013-12-01）：增加禁止日文昵称检测；
- 2.1.0（2013-11-06）：增加禁止日文评论检测；
- 2.0.0（2013-06-02）：增加评论字符长度检测；
- 1.0.2（2010-05-16）：修复发表评论成功后评论内容 Cookie 未清理的问题；
- 1.0.1（2009-11-29）：增加 IP 段过滤功能；
- 1.0.0（2009-11-14）：实现评论内容按屏蔽词过滤和非主文评论过滤。

## 致谢

本分支基于原作者 [YoviSun](https://www.yovisun.com/archive/typecho-plugin-smartspam.html) 的实现

Fork 自 [ShangJixin](https://github.com/ShangJixin/SmartSpam/commits?author=ShangJixin) 的 [SmartSpam](https://github.com/ShangJixin/SmartSpam) 仓库

由饭饭负责维护。
