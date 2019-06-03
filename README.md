# Ppalli (aka dvliveblog)
Micro liveblogging for Wordpress, easy.

## Concept
The whole plugin is built around the idea of giving Wordpress editors a easy-to-integrate liveblogging feature to let them provide a live feed coverage alongside the standard article content.
Goal of this plugin is to offer a lightweight, AJAX polling-based, micro-liveblogging for Wordpress articles with just the basic stuff. No frameworks used, only 1 Javascript external library, nothing more. 

## Features
- WYISWYG editor powered by [TinyMCE](https://www.tiny.cloud/ "TinyMCE")
- Three types of "Moment" (aka single updates) that can be styled via additional CSS.
- Shortcode based implementation: just drop the plugin shortcode in the article content and the plugin will load the liveblogging interface.
- Multiple editors: every liveblog can be updated by multiple editors, allowing for a faster news/event coverage by the staff.
- Customizable polling/refresh rate

## Usage
1.  Create a new article or edit an existing one
2.  Choose where you want to place the liveblogging interface.
3. In that spot, drop the plugin shortcode:
`[liveblog title="This is my live coverage!" secs=3]`
    1. The `title` attribute is optional, however it would be kinda ugly to not include a title to show!
    2. The `secs` attribute is optional, and it specifies the seconds between each refresh of the liveblog Moments. By default is 5 seconds. Lower values offer a (almost) real-time experience for the user, but the server may not be happy of that. 
4. Go on the article page and, if you are logged in and you have the power!, you can start immediately to create Moments for your users! That's it! 

## Future
Although the AJAX-polling mechanism on which this plugin is based does its job, it's far from being the best solution out there. So this plugin will probably die here and will be rewritten from scratch, BUT! suggestions are always open: [mattioleddu@gmail.com](mailto:mattioleddu@gmail.com "mattioleddu@gmail.com") 

#### Fact:
Ppalli as project name was chosen by this author only after a few hours deep into writing code, from Korean adverb 빨리 (pal-li, /p͈a̠ɭʎi/) meaning "quickly". Hence the discordance in the naming inside the source code.
