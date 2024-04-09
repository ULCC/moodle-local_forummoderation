
import { get_string } from "core/str";
class GetString {
    constructor() {
        this.pluginname = null;
    }

    async getString(name) {
        return await get_string(name, "local_forummoderation", "", "");
    }

    async get(name) {
        const result = await this.getString(name);
        return result;
    }
}

export default GetString;