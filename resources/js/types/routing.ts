/**
 * A server-provided navigation or command target, shaped like a Wayfinder `RouteDefinition` so a
 * page can take it as a prop today and a generated helper once its route exists. Pages receive
 * these for Phase 1B routes the server has not published yet; the server stays the only author of
 * where a link or command goes.
 */
export type RouteLink = {
    url: string;
    method: 'get';
};

export type RouteAction = {
    url: string;
    method: 'post';
};
