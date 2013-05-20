= Introduction =

Bambee is a template engine. It uses Smarty syntax, but faster and smaller with less features and little or no memory leaking.

= Backstory =

I was having a problem with Smarty (version 3.0.6) leaking too much memory if you generated several pages subsequently in one session. I needed a solution, with the same interface, but no memory leak.

I have a newsletter system, which uses Smarty as a template language for sending dynamic newsletters easy. You create the body in HTML+Smarty, assign a data feed, a recipient list, preformatted headers and footers, set the timing and off you go.

The data feeds and the recipient lists are coded components for certain purposes, and the preformatted template parts are also part of the repository of the application.

During the sending process I create a template engine instance (Smarty/Bambee) and use that instance to assign the data from the feed, compile the HTML body and send it.

The problem was buried inside Smarty (version 3.0.6). During the compiling it was leaking 10k memory, which grew to fill the 512M memory available for the CLI environment. Extreme...

= The Mission =

So I set out to create a lightweight Template Engine, that can understand basic Smarty syntax, and completes three objectives:
 * understand basic smarty syntax
 * leak less or no memory
 * compile faster

And the result is Bambee, the not so smart(y) template engine.

= Update =

Since then I noticed, that in the current Smarty version (3.0.8) the memory leak is somewhat fixed. Still leaking a, but much less. That would have made Bambee and my work useless, but I was able to to speed bambee up to compile faster than the actual Smarty version, and leak no memory at all.

= Challange the Master =

I use Smarty for 6 years now, and I love the syntax, and the whole concept. By far it is the best template approach I encountered (according to my preference).

So, I think of this project as a challage. A fictional race to beat the master. I will continue the developement. It would never be as smart as Smarty, but hopefully accomplish the objectives that lead to its creation.
