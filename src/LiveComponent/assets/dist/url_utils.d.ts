export declare class UrlUtils extends URL {
    has(key: string): boolean;
    set(key: string, value: any): void;
    get(key: string): any | undefined;
    remove(key: string): void;
    private getData;
    private setData;
}
export declare class HistoryStrategy {
    static replace(url: URL): void;
}
