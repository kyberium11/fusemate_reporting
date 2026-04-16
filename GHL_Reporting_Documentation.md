# GHL Reporting System Documentation

This reporting system is designed to automate the extraction of GHL opportunities, categorize them, and notify a webhook so you can send them via email.

## 1. How it Works
The system follows a 4-step process:
1.  **Fetch**: It connects to GoHighLevel via the **V2 Search API** to grab all "open" opportunities from your specified pipelines.
2.  **Categorize**: It sorts these opportunities into **Inbound**, **Meetings**, and **Deals** based on their `pipelineStageId`.
3.  **Generate**: It uses the `PhpSpreadsheet` library to create a stylized Excel file (`.xlsx`) with hierarchical grouping (Category > Stage > Lead).
4.  **Notify**: It sends a JSON payload to a configured **GHL Webhook URL**.

## 2. How to Generate the Report
You can trigger the report in two ways:

*   **Via Web Browser (URL)**:
    Visit: `https://[your-domain]/generate-report`
    *This is a manual trigger that runs the generation in the background and returns a success response.*
    
*   **Via Command Line (CLI)**:
    Run the following command in your terminal:
    ```bash
    php artisan report:generate
    ```

## 3. What will be the URL?
The reports are stored in the `public/reports` folder. The generated URL will look like this:
`https://[your-domain]/reports/ghl_report_2026-04-16_18-11-25.xlsx`

*Note: The filename includes a timestamp to ensure uniqueness.*

## 4. GHL Workflow Integration (Daily Email)
To automate this, follow these steps in GoHighLevel:

### Step A: Setup the Webhook in GHL
1.  Go to **Automation** -> **Workflows**.
2.  Create a new Workflow and set the trigger to **Inbound Webhook**.
3.  Copy the **Webhook URL** provided by GHL.
4.  Paste this URL into your project's `.env` file:
    ```env
    GHL_WEBHOOK_URL=https://services.leadconnectorhq.com/hooks/...
    ```

### Step B: Configure the Workflow Actions
Once the webhook receives data from the server, it will have these variables:
*   `{{webhook.report_url}}`: The direct link to download the Excel file.
*   `{{webhook.summary_count}}`: Total number of opportunities.
*   `{{webhook.generated_at}}`: Date and time of generation.

1.  Add an **Send Email** action.
2.  In the email body, add a clear call-to-action:
    > "Hello, your daily opportunity report is ready. 
    > [Click here to download the report]({{webhook.report_url}})"

### Step C: Scheduled Trigger (Daily)
If you want the server to run this automatically every day without you clicking a link:
1.  Set up a **Cron Job** on your server to run `php artisan schedule:run` every minute.
2.  Alternatively, use a tool like **Cron-job.org** or GHL's own scheduler to hit the `https://[your-domain]/generate-report` URL once a day.

## Summary of Payloads
When the report is generated, your GHL account receives:
```json
{
  "summary_count": 45,
  "report_url": "https://your-site.com/reports/ghl_report_2026-04-16.xlsx",
  "generated_at": "2026-04-16 18:11:00"
}
```
