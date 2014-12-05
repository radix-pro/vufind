<H4><FONT COLOR="<?ECHO $color_txt?>">Инструкция</FONT></H4>

<B>1. Введение</B>
<P ALIGN="JUSTIFY">
В  данном документе дается краткое описание программы <I>Elib2vf</I>, 
предназначенной для редакторов периодических изданий КФУ (например, 
Казанский экономический вестник, Ученые записки Казанского 
университета и др.), размещающих информацию о своих журналах на сайте 
eLibrary.  Это программа с WEB-интерфейсом, позволяющая через 
стандартный браузер переносить XML-файлы формата eLibrary в 
библиотечную систему VuFind с последующим их индексированием.
</P>

<BR>
<B>2. Схема функционирования</B>
<P ALIGN="JUSTIFY">
Программное обеспечение на сайте eLibrary при размещении очередного 
номера журнала генерирует специальный XML-файл, содержащий мета-
описания всех статей данного номера. Этот файл с помощью браузера 
сначала скачивается редактором на локальный диск, а затем  закачиваетя на 
сайт VuFind, где программа <I>Elib2vf</I> анализирует его, разбивает на отдельные 
статьи в виде XML-файов формата VuFind, которые затем индексируются 
встроенными в VuFind скриптами. 
<BR><BR>
Важно!!! Учитывая, что процесс индексирования требует очень много 
времени, он выполняется ночью (вызывается специальное задание из 
системной программы-планировщика <I>cron</I>). Пользователю лишь 
показывается список XML-файлов со статьями, поставленными в очередь на 
индексацию.
</P>

<BR>
<B>3. Работа с программой</B>
<UL>
<LI><P ALIGN="JUSTIFY">
Войти в VuFind, указав login и пароль.
</P>

<LI><P ALIGN="JUSTIFY">
Если ваш login присутствует в специальном списке доступа, то в нижнем 
меню вы увидете ссылку "Индексипрование eLibrary" (ee адрес:
http://libweb.ksu.ru/vufind/Search/Elib2vf), по которой происходит переход на 
форму загрузки XML-файла eLibrary.
</P>

<LI><P ALIGN="JUSTIFY">
Выбрав этот файл с локального диска и нажав кнопку "Загрузить", вы 
попадаете на форму ввода интернет-адресов полнотекстовых  документов, 
соответствующих статьям индексируемого журнала.
<BR><BR>
Предполагается, что к моменту размещения номера журнала на сайте VuFind 
полнотекстовые файлы статей уже размещены либо на сайте периодического 
издания, либо на сайте eLibrary и их интернет-адреса известны.
<BR><BR>
Допускается ввод только полных адресов (начинающихся с http://). Если все 
полнотекстовые адреса имеют общий префикс (т.е. различаются только 
конечные имена файлов), то этот префикс можно ввести в специальном 
общем поле, а  в статьях указать только конечные имена файлов (как 
правило, они автоматически выбираются из закаченного XML-файла). 
<BR><BR>
Если полнотекстовый документ для статьи отсутствует или его адрес не 
известен, то соответствующее поле должно быть пустым. 
<BR><BR>
Для проверки правильности введенных полнотекстовых адресов 
предусмотрен флажок "Проверять доступность http-ссылок". Если он 
активизирован, то прежде чем начать процедуру индексирования, программа 
проверит наличие документов по указанным адресам и если хоть один из 
них недоступен, то вернется на данную станицу, высветив красным цветом 
неправильные адреса.
</P>

<LI><P ALIGN="JUSTIFY">
Процедура индексирования запускается кнопкой "Выполнить" после ввода 
полнотекстовых адресов. Если по каким-то причинам вы решили отложить  
индексирование, нажмите кнопку "Отказаться".
<BR><BR>
Как уже отмечалось ранее, сам процесс индексирования выполняется ночью, 
о чем программа выводит пользователю соответствующее предупреждение.
</P>

<LI><P ALIGN="JUSTIFY">
Проиндексированные журналы помещаются в коллекцию "Периодические издания КФУ"
</P>
</UL>

<BR>
<A HREF="<?ECHO $elib2vf_home_url?>"><B>Вернуться в программу</B></A>
