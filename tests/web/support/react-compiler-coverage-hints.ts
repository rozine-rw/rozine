import type { NodePath, PluginObj, types } from '@babel/core';

type BabelApi = { types: typeof types };

const compilerRuntimeSource = 'react/compiler-runtime';
const compilerTemporary = /^t\d+$/;
const compilerLabel = /^bb\d+$/;
const ignoreElse = ' v8 ignore else -- @preserve ';
const ignoreNext = ' v8 ignore next -- @preserve ';

/**
 * Keeps coverage measuring authored code once Vitest runs the React Compiler.
 *
 * Tests execute the compiled components, so memoisation bugs surface exactly
 * as they do in the build. The compiler also adds branches nobody wrote, and
 * this plugin marks them with standard v8 ignore hints:
 *
 * - `if ($[n] !== dep) { compute } else { value = $[n] }`: the `else` reads a
 *   value back from an earlier render, reachable only by re-rendering with
 *   unchanged inputs.
 * - `x = tN === undefined ? fallback : tN`: a destructured default. V8 does
 *   not track defaults on their own, so uncompiled they count as covered
 *   whenever the function runs; ignoring the compiled form keeps that.
 * - `bbN: { ...; tN = undefined; }`: an inlined function body falling off its
 *   end, such as an IIFE around an exhaustive `switch`. The source has no
 *   statement there.
 *
 * Oxc drops comments inside expressions, so every hint sits on a statement.
 * This runs in Vitest only; the build never sees it.
 */
export default function reactCompilerCoverageHints({
    types: t,
}: BabelApi): PluginObj {
    const isCacheSlot = (
        node: types.Node,
        cacheNames: ReadonlySet<string>,
    ): boolean =>
        t.isMemberExpression(node) &&
        node.computed &&
        t.isIdentifier(node.object) &&
        cacheNames.has(node.object.name) &&
        t.isNumericLiteral(node.property);

    const readsOnlyCacheSlots = (
        test: types.Expression,
        cacheNames: ReadonlySet<string>,
    ): boolean => {
        if (t.isLogicalExpression(test) && test.operator === '||') {
            return (
                readsOnlyCacheSlots(test.left, cacheNames) &&
                readsOnlyCacheSlots(test.right, cacheNames)
            );
        }

        return (
            t.isBinaryExpression(test) &&
            (test.operator === '!==' || test.operator === '===') &&
            isCacheSlot(test.left, cacheNames)
        );
    };

    /** `tN === undefined ? fallback : tN`, the compiled form of a destructured default. */
    const isDefaultedTemporary = (
        node: types.Node | null | undefined,
    ): boolean =>
        t.isConditionalExpression(node) &&
        t.isBinaryExpression(node.test, { operator: '===' }) &&
        t.isIdentifier(node.test.left) &&
        compilerTemporary.test(node.test.left.name) &&
        t.isIdentifier(node.test.right, { name: 'undefined' }) &&
        t.isIdentifier(node.alternate, { name: node.test.left.name });

    /**
     * Moves a multi-slot test such as `$[0] !== a || $[1] !== b` into its own
     * ignored declaration, because its `||` is a branch too. The same
     * comparisons still run in the same short-circuit order.
     */
    const hoistCacheTest = (branch: NodePath<types.IfStatement>): void => {
        const cacheMissed = branch.scope.generateUidIdentifier('cacheMissed');
        const declaration = t.variableDeclaration('const', [
            t.variableDeclarator(cacheMissed, branch.node.test),
        ]);

        t.addComment(declaration, 'leading', ignoreNext);
        branch.insertBefore(declaration);
        branch.node.test = t.cloneNode(cacheMissed);
    };

    return {
        name: 'rozine-react-compiler-coverage-hints',
        visitor: {
            Program: {
                exit(program: NodePath<types.Program>) {
                    const runtimeNames = new Set<string>();

                    for (const statement of program.node.body) {
                        if (
                            t.isImportDeclaration(statement) &&
                            statement.source.value === compilerRuntimeSource
                        ) {
                            for (const specifier of statement.specifiers) {
                                runtimeNames.add(specifier.local.name);
                            }
                        }
                    }

                    if (runtimeNames.size === 0) {
                        return;
                    }

                    const cacheNames = new Set<string>();

                    program.traverse({
                        VariableDeclarator(declarator) {
                            const { id, init } = declarator.node;

                            if (
                                t.isIdentifier(id) &&
                                t.isCallExpression(init) &&
                                t.isIdentifier(init.callee) &&
                                runtimeNames.has(init.callee.name)
                            ) {
                                cacheNames.add(id.name);
                            }
                        },
                    });

                    program.traverse({
                        IfStatement(branch) {
                            if (
                                !branch.node.alternate ||
                                !readsOnlyCacheSlots(
                                    branch.node.test,
                                    cacheNames,
                                )
                            ) {
                                return;
                            }

                            if (t.isLogicalExpression(branch.node.test)) {
                                hoistCacheTest(branch);
                            }

                            t.addComment(branch.node, 'leading', ignoreElse);
                        },
                        VariableDeclaration(declaration) {
                            if (
                                declaration.node.declarations.some(
                                    (declarator) =>
                                        isDefaultedTemporary(declarator.init),
                                )
                            ) {
                                t.addComment(
                                    declaration.node,
                                    'leading',
                                    ignoreNext,
                                );
                            }
                        },
                        LabeledStatement(labelled) {
                            const { label, body } = labelled.node;
                            const last = t.isBlockStatement(body)
                                ? body.body.at(-1)
                                : undefined;

                            if (
                                compilerLabel.test(label.name) &&
                                t.isExpressionStatement(last) &&
                                t.isAssignmentExpression(last.expression) &&
                                t.isIdentifier(last.expression.left) &&
                                compilerTemporary.test(
                                    last.expression.left.name,
                                ) &&
                                t.isIdentifier(last.expression.right, {
                                    name: 'undefined',
                                })
                            ) {
                                t.addComment(last, 'leading', ignoreNext);
                            }
                        },
                        ExpressionStatement(statement) {
                            const { expression } = statement.node;

                            if (
                                t.isAssignmentExpression(expression) &&
                                isDefaultedTemporary(expression.right)
                            ) {
                                t.addComment(
                                    statement.node,
                                    'leading',
                                    ignoreNext,
                                );
                            }
                        },
                    });
                },
            },
        },
    };
}
