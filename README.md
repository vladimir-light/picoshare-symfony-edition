About
---
This is a recreation (almost 1:1 clone but with some adjustments) of [PicoShare](https://github.com/mtlynch/picoshare) by [Michael Lynch](https://mtlynch.io/).
This project built with [Symfony](https://symfony.com/), [Twig](https://twig.symfony.com/) and [Doctrine](https://www.doctrine-project.org/).

What is PicoShare?
---

PicoShare is a minimalist service that allows you to share files easily.

On PicoShare, only you can upload files (not only images). You can share links to those files with anyone, and they never have to sign up for an account.
PicoShare is easy to **self-host**.

Why?
---

I use the self-hosted version of PicoShare by myself. I have always been fascinated by the simplicity and elegance of PicoShare.
Originally, I wanted to use it as a “copycat project” to learn new programming languages, especially when it comes to web projects. I just didn't want to create another to-do list ...again.

After examining the source code of PicoShare a little more closely, I realized that simply copying it while using a “new/unknown” language would **not work at all!** First, my Go-Lang skills are not sufficient enough. Second, PicoShare has more to it than meets the eye.

So I decided to start by copying the original (not 100% 1:1 clone, but very close) using a stack I'm most familiar with (PHP, Symfony and Doctrine). That way I could "reverse engineer it" without distracting myself with "how to do *this* with *that* language."

That's how PicoShare Symfony Edition came about. 


To-Do
---

-[x] [general] Essential functionality (upload+download)
-[x] [admin] Essential admin-CRUD (files, guest-links)
-[x] [general] primitive tests 👶
-[ ] [admin] -> Settings and Info routes and controllers
-[ ] [upload] -> auto splitting files in chunks with fixed size 
-[ ] [general] -> API-Controllers 
-[ ] [upload] -> Fine-tune **memory-usage / memory-leaks** for bigger files.
-[ ] [css/js] -> Move custom CSS/JS to separate files. 
-[ ] [ui/ux] -> Darg&Drop + Dropzone + async uploads



