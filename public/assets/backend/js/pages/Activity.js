import Message from "../../../../vendor/skeletorjs/src/Message/Message.js";
import CrudPage from "../../../../vendor/skeletorjs/src/Page/CrudPage.js";


export default class Activity extends CrudPage {
    preload() {
        this.setDataTableAction({
            name: 'restore',
            label: 'Restore',
            content: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M125.7 160H176c17.7 0 32 14.3 32 32s-14.3 32-32 32H48c-17.7 0-32-14.3-32-32V64c0-17.7 14.3-32 32-32s32 14.3 32 32v51.2L97.6 97.6c87.5-87.5 229.3-87.5 316.8 0s87.5 229.3 0 316.8s-229.3 87.5-316.8 0c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0c62.5 62.5 163.8 62.5 226.3 0s62.5-163.8 0-226.3s-163.8-62.5-226.3 0L125.7 160z"/></svg>',
            order: 1,
            promptMessage: 'Are you sure you want to restore the entity to this point in time?',
            useLoader: true,
            lockRowDuringCallback: true,
            callback: async (entity) => {
                try {
                    const req = await fetch(`/activity/restore/`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `id=${entity.id}`
                    });
                    const res = await req.json();
                    if (res.status) {
                        Message.spawn({
                            message: res.message,
                            type: Message.TYPES.SUCCESS,
                            view: {
                                container: this.getMessagesContainerFixed(),
                                type: Message.VIEW_TYPES.NOTIFICATION,
                            },
                            ephemeralTimeout: 5000
                        });
                        this.reloadTable(true);
                    } else {
                        const errorMsg = res.generalErrors?.[0]?.message || 'Restore failed.';
                        Message.spawn({
                            message: errorMsg,
                            type: Message.TYPES.ERROR,
                            view: {
                                container: this.getMessagesContainerFixed(),
                                type: Message.VIEW_TYPES.NOTIFICATION,
                            }
                        });
                    }
                } catch (e) {
                    Message.spawn({
                        message: 'An error occurred during restore.',
                        type: Message.TYPES.ERROR,
                        view: {
                            container: this.getMessagesContainerFixed(),
                            type: Message.VIEW_TYPES.NOTIFICATION,
                        }
                    });
                }
            }
        });
    }
}
