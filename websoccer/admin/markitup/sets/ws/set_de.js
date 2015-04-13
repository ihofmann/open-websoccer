// ----------------------------------------------------------------------------
// markItUp!
// ----------------------------------------------------------------------------
// Copyright (C) 2011 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// Html tags
// http://en.wikipedia.org/wiki/html
// ----------------------------------------------------------------------------
// Basic set. Feel free to add more tags
// ----------------------------------------------------------------------------
var mySettings = {
	onCtrlEnter:  	{keepDefault:false, openWith:'\n<p>', closeWith:'</p>'},
	onTab:    		{keepDefault:false, replaceWith:'    '},
	markupSet:  [ 	
		{name:'Fett', key:'B', openWith:'(!(<strong>|!|<b>)!)', closeWith:'(!(</strong>|!|</b>)!)' },
		{name:'Kursiv', key:'I', openWith:'(!(<em>|!|<i>)!)', closeWith:'(!(</em>|!|</i>)!)'  },
		{name:'Durchgestrichen', key:'S', openWith:'<del>', closeWith:'</del>' },
		{separator:'---------------' },
		{name:'Liste mit Punkten', openWith:'    <li>', closeWith:'</li>', multiline:true, openBlockWith:'<ul>\n', closeBlockWith:'\n</ul>'},
		{name:'Numerische Liste', openWith:'    <li>', closeWith:'</li>', multiline:true, openBlockWith:'<ol>\n', closeBlockWith:'\n</ol>'},
		{separator:'---------------' },
		{name:'Bild', key:'P', replaceWith:'<img src="[![Source:!:http://]!]" alt="[![Alternativtext]!]" />' },
		{name:'Hyperlink', key:'L', openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Tooltip (Title)]!]")!)>', closeWith:'</a>', placeHolder:'Text zum Link...' },
		{separator:'---------------' },
		{name:'Formatierung entfernen', className:'clean', replaceWith:function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") } },		
		{name:'Vorschau', className:'preview',  call:'preview'}
	],
	previewParser: function(content) {
		return content.replace(/\n/g, '<br />');
	}
}
