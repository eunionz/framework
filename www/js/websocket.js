var EunionzWebsocket = {
    url: "",
    socket: null,
    header: {},
    init: function () {
        if (typeof WebSocket == 'undefined') {
            alert("浏览器不支持 WebSocket，请升级或更换浏览器！！");
            return;
        }
        if(arguments.length==1){
            this.url = arguments[0];
        }
        if (!this.url) {
            alert("WebSocket Url为能为空，创建 WebSocket失败！！");
            return;
        }
        this.socket = new WebSocket(this.url);
        this.socket.onopen = this.onopen;
        this.socket.onclose = this.onclose;
        this.socket.onmessage = this.onmessage;
        if (js_headers !== undefined) {
            this.header = js_headers;
        }
    },
    setUrl: function (url) {
        this.url = url;
    },
    getUrl: function () {
        return this.url;
    },
    onopen: function (e) {
        console.log("connected to " + wsuri);
    },
    onclose: function (e) {
        console.log("connection closed (" + e.code + ")");
    },
    onmessage: function (e) {
        alert("收到数据：" + e.data);
    },
    setHeader: function (name, value) {
        if (!this.socket) {
            alert("浏览器不支持 WebSocket，请升级或更换浏览器！！");
            return;
        }
        this.header[name] = value;
    },
    send: function (data) {
        if (!this.socket) {
            alert("浏览器不支持 WebSocket，请升级或更换浏览器！！");
            return;
        }
        this.setHeader('url' , this.url);
        this.socket.send(JSON.stringify({"header": this.header, "body": data}));
    }

};
