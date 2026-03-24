import process from 'node:process';
import webpush from 'web-push';

async function main() {
    const chunks = [];

    for await (const chunk of process.stdin) {
        chunks.push(chunk);
    }

    const raw = Buffer.concat(chunks).toString('utf8');
    const { subscription, payload, vapid } = JSON.parse(raw);

    webpush.setVapidDetails(vapid.subject, vapid.publicKey, vapid.privateKey);

    await webpush.sendNotification(subscription, JSON.stringify(payload));
    process.stdout.write('ok');
}

main().catch((error) => {
    const statusCode = error?.statusCode ? ` ${error.statusCode}` : '';
    const body = error?.body ? ` ${error.body}` : '';
    process.stderr.write(`${error?.message ?? 'Web push failed'}${statusCode}${body}`);
    process.exit(1);
});
