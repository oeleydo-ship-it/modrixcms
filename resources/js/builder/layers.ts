import type { OutlineNode } from './tree';

/** Search keeps each match's ancestry so even deeply nested results have context. */
export function visibleLayers(nodes: OutlineNode[], query: string, collapsed: ReadonlySet<string>): OutlineNode[] {
    const term = query.trim().toLowerCase();
    const ancestors: number[] = [];
    const matches = new Set<number>();
    const visible: OutlineNode[] = [];

    nodes.forEach((node, index) => {
        ancestors.length = node.depth;
        if (term) {
            if (`${node.label} ${node.ref.kind}`.toLowerCase().includes(term)) {
                matches.add(index);
                ancestors.forEach(parent => matches.add(parent));
            }
        } else if (!ancestors.some(parent => collapsed.has(nodes[parent]!.ref.id))) {
            visible.push(node);
        }
        ancestors[node.depth] = index;
    });

    return term ? nodes.filter((_, index) => matches.has(index)) : visible;
}
