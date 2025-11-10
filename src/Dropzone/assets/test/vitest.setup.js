class MockDataTransferItemList {
    constructor(files) {
        this._files = files;
    }

    add(file) {
        this._files.push(file);
    }

    remove(index) {
        this._files.splice(index, 1);
    }

    clear() {
        this._files.length = 0;
    }
}

class MockDataTransfer {
    constructor() {
        this.files = [];
        this.items = new MockDataTransferItemList(this.files);
    }

    setData() {}
    getData() {
        return '';
    }
    clearData() {
        this.files.length = 0;
    }
}

globalThis.DataTransfer = MockDataTransfer;
