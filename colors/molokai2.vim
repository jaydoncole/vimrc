" Author: Kim Silkebækken <kim.silkebaekken+vim@gmail.com>
" Source repository: https://github.com/Lokaltog/vim-distinguished

" Initialization {{{
	set background=dark

	hi clear
	if exists('syntax_on')
		syntax reset
	endif

	let g:colors_name = 'distinguished'

	if ! has('gui_running')
		if &t_Co != 256
			echoe 'The ' . g:colors_name . ' color scheme requires gvim or a 256-color terminal'

			finish
		endif
	endif
" }}}
" Color dictionary parser {{{
	function! s:ColorDictParser(color_dict)
		for [group, group_colors] in items(a:color_dict)
			exec 'hi ' . group
				\ . ' ctermfg=' . (group_colors[0] == '' ? 'NONE' :       group_colors[0])
				\ . ' ctermbg=' . (group_colors[1] == '' ? 'NONE' :       group_colors[1])
				\ . '   cterm=' . (group_colors[2] == '' ? 'NONE' :       group_colors[2])
				\
				\ . '   guifg=' . (group_colors[3] == '' ? 'NONE' : '#' . group_colors[3])
				\ . '   guibg=' . (group_colors[4] == '' ? 'NONE' : '#' . group_colors[4])
				\ . '     gui=' . (group_colors[5] == '' ? 'NONE' :       group_colors[5])
		endfor
	endfunction
" }}}

"	   | Highlight group         | CTFG | CTBG |  CTAttributes |   GUIFG |    GUIBG |   GUIAttributes |
"	   |-------------------------|------|------|---------------|---------|----------|-----------------|
call s:ColorDictParser({
	\   'Normal'               : [   252,   233,             '', 'F8F8F2',  '111212',               '']
	\ , 'Visual'               : [    '',   235,             '',       '',  '403D3D',               '']
    \
	\ , 'Cursor'               : [    '',    '',             '', 'ffffff',  'dd4010',               '']
	\ , 'lCursor'              : [    '',    '',             '', 'ffffff',  '89b6e2',               '']
	\ , 'CursorLine'           : [    '',   236,             '',       '',  '3a3a3a',               '']
	\ , 'CursorLineNr'         : [   231,   240,             '', 'ffffff',  '585858',               '']
	\ , 'CursorColumn'         : [   231,   237,             '', 'ffffff',  '3a3a3a',               '']
    \
	\ , 'Folded'               : [    67,    16,             '', '465457',  '000000',               '']
	\ , 'FoldColumn'           : [    67,    16,             '', '465457',  '000000',               '']
	\ , 'SignColumn'           : [   118,   235,         'bold', 'A6E22E',  '232526',               '']
	\ , 'ColorColumn'          : [    '',   236,             '',       '',  '232526',               '']
	\ , 'StatusLine'           : [   238,   253,         'bold', '455354',  'F8F8F2',           'bold']
	\ , 'StatusLineNC'         : [   244,   232,             '', '808080',  '080808',               '']
	\ , 'LineNr'               : [   243,   '',             '', '465457',  '000000',               '']
	\ , 'VertSplit'            : [   244,   232,             '', '5B2948',  '080808',               '']
    \
	\ , 'WildMenu'             : [    81,    16,             '', '66D9EF',  '000000',               '']
	\ , 'Directory'            : [   118,    '',         'bold', 'A6E22E',        '',           'bold']
	\ , 'Underlined'           : [   244,    '',    'underline', '808080',        '',      'underline']
	\ , 'Question'             : [    81,    '',             '', '66D9EF',        '',               '']
	\ , 'MoreMsg'              : [   229,    '',             '', 'E6DB74',        '',               '']
	\ , 'WarningMsg'           : [   231,   238,         'bold', 'ffffff',  '333333',           'bold']
	\ , 'ErrorMsg'             : [   199,    16,         'bold', 'f92672',  '232526',           'bold']
    \
	\ , 'Comment'              : [    59,    '',             '', '7E8E91',        '',               '']
	\ , 'vimCommentTitleLeader': [   250,   233,             '', 'bcbcbc',  '121212',               '']
	\ , 'vimCommentTitle'      : [   250,   233,             '', 'bcbcbc',  '121212',               '']
	\ , 'vimCommentString'     : [   245,   233,             '', '8a8a8a',  '121212',               '']
    \
    \ , 'TabLineSel'           : [   231,   238,             '', '212121',  'B7B7B7',             'bold' ]
	\ , 'TabLine'              : [   255,    '',         'bold', '9A9A9A',  '1E1E1E',           '']
	\ , 'TabLineFill'          : [   240,   238,             '', '1B1D1E',  '1B1D1E',               '']
	\ , 'TabLineNumber'        : [   160,   238,         'bold', 'd70000',  '444444',           'bold']
	\ , 'TabLineClose'         : [   245,   238,         'bold', '9A9A9A',  '545454',           '']
	\ , 'SpellCap'             : [    '',    17,             '', '707070',        '',      'undercurl']
	\ , 'SpecialKey'           : [    59,    '',             '', '465457',        '',               '']
	\ , 'NonText'              : [    59,    '',             '', '465457',        '',               '']
	\ , 'MatchParen'           : [   233,    208,        'bold', '000000',  'FD971F',           'bold']
	\
	\ , 'Constant'             : [   135,    '',             '', 'AE81FF',        '',           'bold']
	\ , 'Special'              : [    81,    '',             '', '66D9EF',        '',               '']
	\ , 'Identifier'           : [   208,    '',             '', 'FD971F',        '',               '']
	\ , 'Statement'            : [   161,    '',             '', 'F92672',        '',           'bold']
	\ , 'PreProc'              : [   118,    '',             '', 'A6E22E',        '',               '']
	\ , 'Type'                 : [    81,    '',             '', '66D9EF',        '',               '']
	\ , 'String'               : [   144,    '',             '', 'E6DB74',        '',               '']
	\ , 'Number'               : [   135,    '',             '', 'AE81FF',        '',               '']
	\ , 'Define'               : [    81,    '',             '', '66D9EF',        '',               '']
	\ , 'Error'                : [   219,    89,             '', 'E6DB74',  '1E0010',               '']
	\ , 'Function'             : [   118,    '',             '', 'A6E22E',        '',               '']
    \
	\ , 'Include'              : [   173,    '',             '', 'FF3A4C',        '',               '']
    \
	\ , 'PreCondit'            : [   118,    '',             '', 'A6E22E',        '',           'bold']
	\ , 'Keyword'              : [   173,    '',             '', 'F92672',        '',           'bold']
	\ , 'Search'               : [     0,   166,             '', '000000',  'FFE792', 'underline,bold']
	\ , 'Title'                : [   166,    '',             '', 'EF5939',        '',               '']
	\ , 'Delimiter'            : [   241,    '',             '', '8F8F8F',        '',               '']
	\ , 'StorageClass'         : [   208,    '',             '', 'FD971F',        '',               '']
	\ , 'Operator'             : [   161,    '',             '', 'F92672',        '',               '']
    \
	\ , 'TODO'                 : [   228,    94,         'bold', 'ffff87',  '875f00',           'bold']
	\ , 'SyntasticWarning'     : [   220,    94,             '', 'ffff87',  '875f00',           'bold']
	\ , 'SyntasticError'       : [   202,    52,             '', 'ffff87',  '875f00',           'bold']
    \
	\ , 'Pmenu'                : [    81,    16,             '', '66D9EF',  '000000',               '']
	\ , 'PmenuSel'             : [   255,   242,             '',       '',  '808080',               '']
	\ , 'PmenuSbar'            : [    '',   232,             '',       '',  '080808',               '']
    \
	\ , 'CTagsImport'          : [   109,    '',             '', '87afaf',        '',               '']
	\ , 'CTagsClass'           : [   221,    '',             '', 'ffd75f',        '',               '']
	\ , 'CTagsFunction'        : [   109,    '',             '', '87afaf',        '',               '']
	\ , 'CTagsGlobalVariable'  : [   253,    '',             '', 'dadada',        '',               '']
	\ , 'CTagsMember'          : [   145,    '',             '', 'afafaf',        '',               '']
	\
	\ , 'xmlTag'               : [   149,    '',             '', 'afd75f',        '',           'bold']
	\ , 'xmlTagName'           : [   250,    '',             '', 'bcbcbc',        '',               '']
	\ , 'xmlEndTag'            : [   209,    '',             '', 'ff875f',        '',           'bold']
	\
	\ , 'cssImportant'         : [   166,    '',             '', 'd75f00',        '',           'bold']
	\
	\ , 'DiffAdd'              : [    '',    24,             '',       '',  '13354A',               '']
	\ , 'DiffChange'           : [   181,    239,            '', '89807D',  '4C4745',               '']
	\ , 'DiffDelete'           : [   162,    53,             '', '960050',  '1E0010',               '']
	\ , 'DiffText'             : [    '',   102,         'bold',       '',  '4C4745',           'bold']
	\ , 'diffLine'             : [    68,    '',         'bold', '5f87d7',        '',           'bold']
	\ , 'diffFile'             : [   242,    '',             '', '6c6c6c',        '',               '']
	\ , 'diffNewFile'          : [   242,    '',             '', '6c6c6c',        '',               '']
    \
    \ , 'BufTabLineCurrent'    : [   135,    '',             '', 'ffffff',        'AE81FF',           'bold']
\ })

hi link htmlTag            xmlTag
hi link htmlTagName        xmlTagName
hi link htmlEndTag         xmlEndTag

hi link phpCommentTitle    vimCommentTitle
hi link phpDocTags         vimCommentString
hi link phpDocParam        vimCommentTitle

hi link diffAdded          DiffAdd
hi link diffChanged        DiffChange
hi link diffRemoved        DiffDelete
