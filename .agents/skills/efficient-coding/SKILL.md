---
name: efficient-coding
description: Minimize unnecessary repository exploration, context usage, tool calls, refactoring, and output while maintaining correctness. Use for normal Asena implementation and maintenance tasks.
---

# Efficient Coding

Start with files directly relevant to the task. Search before opening large files, expand exploration only when current information is insufficient, and do not repeatedly reread unchanged files or inspect unrelated modules.

Make the smallest complete change. Prefer existing components, utilities, patterns, and dependencies. Avoid speculative refactoring, unrelated cleanup, unnecessary abstractions, and new dependencies.

After implementation, inspect the changed code and run the smallest relevant targeted tests, type checks, or lint commands.
