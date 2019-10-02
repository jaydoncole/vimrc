let mapleader = "\<Space>"
set nocompatible
" Vundle Handling
filetype off
set rtp+=~/.vim/bundle/Vundle.vim
call vundle#begin()
Plugin 'gmarik/Vundle.vim'

Bundle 'airblade/vim-gitgutter' 
Bundle 'tpope/vim-fugitive'
Bundle 'majutsushi/tagbar'
Bundle 'vim-php/tagbar-phpctags.vim'
Bundle 'captbaritone/better-indent-support-for-php-with-html'
Bundle 'jeetsukumaran/vim-buffergator'
Bundle 'vim-scripts/vim-auto-save'
Bundle 'mileszs/ack.vim'
Bundle 'itchyny/lightline.vim'
Bundle 'easymotion/vim-easymotion'
Bundle 'Raimondi/delimitMate'
Bundle 'joonty/vdebug'
Bundle 'ap/vim-buftabline'
Bundle 'scrooloose/nerdtree'
Bundle 'Xuyuanp/nerdtree-git-plugin'
Bundle 'vim-scripts/taglist.vim'
Bundle 'junegunn/fzf'
Bundle 'junegunn/fzf.vim'
Bundle 'powerline/powerline-fonts'
Bundle 'dbakker/vim-projectroot'
Bundle 'w0rp/ale'
Bundle 'skywind3000/vim-preview'
Bundle 'mattn/emmet-vim'
Bundle 'tpope/vim-surround'
Bundle 'posva/vim-vue'
Bundle 'alvan/vim-closetag'
Bundle 'Yggdroot/indentLine'
Bundle 'yssl/QFEnter'
Bundle 'zxqfl/tabnine-vim'

"// Breaks PHP ctrl+* support
"Bundle 'pangloss/vim-javascript'
"// Breaks color matching
"Bundle 'gko/vim-coloresque'

call vundle#end()

filetype plugin indent on
let b:PHP_default_indenting=1

let g:gitgutter_enabled = 1
let g:gitgutter_diff_base='HEAD'

" Vim-Closetag Setings
let g:closetag_filenames = '*.html, *.xhtml, *.phtml, *.vue, *.php'

let NERDTreeMinimalUI = 1
let NERDTreeDirArrows = 1
nnoremap <silent> <Leader>v :NERDTreeFind<CR>
let NERDTreeAutoDeleteBuffer = 1

set completeopt=menu,menuone,preview,noselect,noinsert

autocmd BufEnter * :syntax sync fromstart

"NerdTree
let g:NERDTreeHijackNetrw=0

"Limelight
let g:limelight_conceal_guifg='LightSlateGray'

runtime macros/matchit.vim

" Colors and Highlight Handling
set t_Co=256

syntax enable

"Spaces & Tabs
set tabstop=4
set softtabstop=4
set shiftwidth=4
set expandtab
set wrap linebreak nolist
" UI options
set history=50
set ruler
set showcmd
set incsearch
set number
set wildmenu
set showmatch
set mouse=a
set laststatus=2
set hidden
set autoread
" Display a vertical right margin
set diffopt=vertical
"Mode is redundent with lightline
set noshowmode

let g:netrw_preview = 1
let g:netrw_liststyle = 3
let g:netrw_winsize = 30

" Find CTag files
set tags=./.git/project.tags,.git/project.tags;$HOME
nmap ,t :!(cd %:p:h;ctags *)&


"Move backup files to the /tmp directory
set backup
set backupdir=~/.vim-tmp,~/.tmp,~/tmp,/var/tmp,/tmp
set backupskip=/tmp/*,/private/tmp/*
set directory=~/.vim-tmp,~/.tmp,~/.tmp,/var/tmp,/tmp
set writebackup

" Only do this part when compiled with support for autocommands.
if has("autocmd")

  augroup vimrcEx
  au!

  " When editing a file, always jump to the last known cursor position.
  " Don't do it when the position is invalid or when inside an event handler
  autocmd BufReadPost *
    \ if line("'\"") > 1 && line("'\"") <= line("$") |
    \   exe "normal! g`\"" |
    \ endif

  "Highlight variables that match the current position of the curser
  autocmd CursorMoved * exe printf('match IncSearch /\V\<%s\>/', escape(expand('<cword>'), '/\'))
  autocmd CursorMovedI * :PreviewSignature!


  augroup END

endif " has("autocmd")

"Map the TagBar to f8
:nmap <f8> :TagbarToggle<CR>
let g:tagbar_autofocus = 1
let g:tagbar_autoclose = 1
let g:tagbar_phpctags_bin='/home/Auxiant.local/jcole/.vim/phpctags'
let g:tagbar_phpctags_member_limit = '512M'
"Map CtrlP to Ctrl+P
:nmap <c-p> :FZF<CR>
" Map NerdTree to Ctrl+n
:nmap <C-n> :NERDTreeToggle<CR>
" Map taglist to f9
:nmap <f9> :TlistOpen<CR>


let g:buffergator_viewport_split_policy = "N"

map <Leader>s <Plug>(easymotion-overwin-f)

let g:vdebug_features = {
\    "max_depth" : 4000,
\   "max_data" : 8000,
\   "max_children" : 8000
\}

let g:vdebug_options = {
\   "watch_window_style" : 'compact',
\   "continuous_mode" : 1
\}

let g:lightline = {
  \ 'colorscheme' : 'MyCustom2',
  \ 'mode_map': { 'c': 'NORMAL' },
  \ 'active': {
  \   'left': [ [ 'mode', 'paste' ], [ 'fugitive', 'filename' ], ['ctrlpmark', 'tagbar'] ]
  \ },
  \ 'component': {
  \   'tagbar': '%{tagbar#currenttag("%s", "", "s")}',
  \ },
  \ 'component_function': {
  \   'modified': 'LightLineModified',
  \   'readonly': 'LightLineReadonly',
  \   'fugitive': 'LightLineFugitive',
  \   'filename': 'LightLineFilename',
  \   'fileformat': 'LightLineFileformat',
  \   'filetype': 'LightLineFiletype',
  \   'fileencoding': 'LightLineFileencoding',
  \   'mode': 'LightLineMode',
  \ },
    \ 'separator': { 'left': '', 'right': '' },
    \ 'subseparator': { 'left': '', 'right': '' }
\ }
set laststatus=2

colorscheme badwolf

"GVIM options
if has('gui_running')
    :let g:lightline = {
      \ 'colorscheme' : 'MyCustom2',
      \ 'mode_map': { 'c': 'NORMAL' },
      \ 'active': {
      \   'left': [ [ 'mode', 'paste' ], [ 'fugitive', 'filename' ], ['ctrlpmark', 'tagbar'] ]
      \ },
      \ 'component': {
      \   'tagbar': '%{tagbar#currenttag("[%s]", "", "f")}',
      \ },
      \ 'component_function': {
      \   'modified': 'LightLineModified',
      \   'readonly': 'LightLineReadonly',
      \   'fugitive': 'LightLineFugitive',
      \   'filename': 'LightLineFilename',
      \   'fileformat': 'LightLineFileformat',
      \   'filetype': 'LightLineFiletype',
      \   'fileencoding': 'LightLineFileencoding',
      \   'mode': 'LightLineMode',
      \ },
        \ 'separator': { 'left': '', 'right': '' },
        \ 'subseparator': { 'left': '', 'right': '' }
    \ }

  :set guioptions-=m "Ditch the menu bar
  :set guioptions-=T "Drop the toolbar
  :set guioptions-=r "Nix the right scrollbar
  :set guioptions-=L "Kill the left scrollbar
  :set guifont=Source\ Code\ Pro\ for\ Powerline\ 9
  :set linespace=3
  let g:auto_save=1
  let g:auto_save_in_insert_mode=0

   colorscheme molokai2
endif

let g:buftabline_numbers = 1
let g:buftabline_seperators = 1
nmap <leader>1 <Plug>BufTabLine.Go(1)
nmap <leader>2 <Plug>BufTabLine.Go(2)
nmap <leader>3 <Plug>BufTabLine.Go(3)
nmap <leader>4 <Plug>BufTabLine.Go(4)
nmap <leader>5 <Plug>BufTabLine.Go(5)
nmap <leader>6 <Plug>BufTabLine.Go(6)
nmap <leader>7 <Plug>BufTabLine.Go(7)
nmap <leader>8 <Plug>BufTabLine.Go(8)
nmap <leader>9 <Plug>BufTabLine.Go(9)
nmap <leader>0 <Plug>BufTabLine.Go(10)


function! LightLineModified()
  return &ft =~ 'help\|vimfiler\|gundo' ? '' : &modified ? '+' : &modifiable ? '' : '-'
endfunction

function! LightLineReadonly()
  return &ft !~? 'help\|vimfiler\|gundo' && &readonly ? '⭤' : ''
endfunction

function! LightLineFilename()
  return ('' != LightLineReadonly() ? LightLineReadonly() . ' ' : '') .
        \ (&ft == 'vimfiler' ? vimfiler#get_status_string() :
        \  &ft == 'unite' ? unite#get_status_string() :
        \  &ft == 'vimshell' ? vimshell#get_status_string() :
        \ '' != expand('%:t') ? expand('%:t') : '[No Name]') .
        \ ('' != LightLineModified() ? ' ' . LightLineModified() : '')
endfunction

function! LightLineFugitive()
  if &ft !~? 'vimfiler\|gundo' && exists("*fugitive#head")
    let _ = fugitive#head()
    return strlen(_) ? ' '._ : ''
  endif
  return ''
endfunction

function! LightLineFileformat()
  return winwidth(0) > 70 ? &fileformat : ''
endfunction

function! LightLineFiletype()
  return winwidth(0) > 70 ? (strlen(&filetype) ? &filetype : 'no ft') : ''
endfunction

function! LightLineFileencoding()
  return winwidth(0) > 70 ? (strlen(&fenc) ? &fenc : &enc) : ''
endfunction

function! LightLineMode()
  return winwidth(0) > 60 ? lightline#mode() : ''
endfunction

"Syntastic
set statusline+=%#Warningmsg#
set statusline+=%{SyntasticStatuslineFlag()}
set statusline+=%*

set completeopt+=preview
let g:ale_completion_enabled = 0
let g:ale_lint_on_insert_leave = 1
let g:ale_php_langserver_use_global = 1
let g:ale_php_langserver_executable = $HOME.'/projects/PLS/vendor/felixfbecker/language-server/bin/php-language-server.php'
let g:ale_completion_delay = 200

let g:pdv_template_dir = $HOME . "/.vim/bundle/pdv/templates_snip"
map <Leader>p :call pdv#DocumentCurrentLine()<CR>

let delimitMate_matchpairs = "(:),[:],{:}"
let delimitMate_expand_cr = 0

"Docker debug
function! SetupDebug()
  let g:vdebug_options['path_maps'] = {'/var/share/nginx/html/current/': call('projectroot#get', a:000)}
  " Hack to override vdebug options
  source ~/.vim/bundle/vdebug/plugin/vdebug.vim
endfunction
autocmd VimEnter * :call SetupDebug()

" Strip trailing white space
function! <SID>StripTrailingWhitespace()
    " Preparation: save last search, and cursor position.
    let _s=@/
    let l = line(".")
    let c = col(".")
    " Do the business:
    %s/\s\+$//e
    " Clean up: restore previous search history, and cursor position
    let @/=_s
    call cursor(l, c)
endfunction
nmap <silent> <Leader><space> :call <SID>StripTrailingWhitespace()<CR>
nmap <leader>p <Plug>yankstack_substitute_older_paste
nmap <leader>P <Plug>yankstack_substitute_newer_paste

"Automatically try to determine the proejct root and set it
function! <SID>AutoProjectRootCD()
  try
    if &ft != 'help'
      ProjectRootCD
    endif
  catch
    " Silently ignore invalid buffers
  endtry
endfunction

autocmd BufEnter * call <SID>AutoProjectRootCD()
