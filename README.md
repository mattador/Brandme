BrandMe
=======

BrandMe was a SaaS platform which I built to affiliate social media content creators with businesses who wanted to explore alternative means of advertising through sponsored social marketing. The platform was essentially a highly experimental bartering subscription platform which leveraged popular social media APIs to do the heavy lifting. The code in this repository is a partial snapshot of the second complete rewrite of the platform, which I developed from scratch. I've uploaded it for my own reference.

Background
==========

I built the first version of the platform leveraging Python and MongoDb. The amount of traffic we experienced shortly after launching immediately highlighted a number of critical problems caused by inadequate data design considerations and unnecessary overengineering and design pattern abuse, which also resulted in performance bottlenecks rather than the intended extensibility.

Based on these lessons, I went back to the drawing board with performance exclusively in mind. I rebuilt the platform using Phalcon PHP (pre-PHP 7), MySQL, Elastic Search, Redis and a simple messaging queue. In hindsight, I again made several questionable development decisions which came back to haunt me later on. For instance, I made heavy use of raw SQL tightly coupled with business logic, in many places, in order avoid the overhead of an actual ORM, but this only made it difficult to automate testing and track down bugs.
