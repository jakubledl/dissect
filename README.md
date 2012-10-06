Dissect: lexing and parsing tools in pure PHP
=============================================

Dissect is a very lightweight library of tools for
your lexical and syntactical analysis needs. It offers:

- Two lexers, a simple one and a stateful one under the namespace *Dissect\Lexer*.
- A LALR(1) parser (generator) under *Dissect\Parser\LALR1*.
- The class *Dissect\Node\CommonNode* so you don't have to keep writing
  your AST base class over and over.

**WARNING!** This is a very young project (first commit was about 4
hours ago, actually). I believe it's theoretically usable if you're willing to risk or
experiment, but few things *will* change before I even given give it a
version number:

- API. Dissect was written in a bit of proof-of-concept way, so it has a
  maximally explicit API, which has, naturally, horrid usability. It
  will be rewritten and only after this rewrite will I write any
  documentation.

- Currently, the tokens contain information about the line and offset
  they were recognized at. I'll have to consider the practical usability
  of knowing the offset, since calculating it is currently done in
  [quite a hacky way][offset-calculation] and I would probably be happy
  to get rid of it.

- Probably more.

But in the meantime, thanks for coming by and feel free to try it out :-)

  [offset-calculation]: https://github.com/jakubledl/dissect/blob/master/src/Dissect/Lexer/AbstractLexer.php#L99
