# Symfony/AWS Bedrock: ChatBot
Proof-of-concept AI chatbot using [RAG](https://aws.amazon.com/what-is/retrieval-augmented-generation/) to answer questions with information stored in a Knowledge Base.

The idea here is to upload a help guide to an S3 bucket and have an AI chatbot learn it.

End-to-end workflow:
1. Text files are uploaded to S3, containing the knowledge base content (e.g. help articles, documentation).
2. Amazon Bedrock Knowledge Base ingests, chunks, and embeds these files into a managed vector store.
3. A Bedrock Agent is linked to the Knowledge Base and configured with instructions on how to respond.
4. Symfony sends user questions to the Bedrock Agent using the InvokeAgent API.
5. The agent retrieves relevant context from the vector store and passes it with the question to an LLM.
6. The LLM generates a natural language answer, which is returned to the Symfony app.

## (1) Setup AWS

### 1. Create IAM User
Docs: https://console.aws.amazon.com/iam/home#/users

1. Go to [IAM Console > Users](https://console.aws.amazon.com/iam/home#/users)
    - Click `Create User`
    - Enter a `User name` and click `Next`
    - Don't add a group, click `Next`
    - Click `Create user`
2. Open the newly created User
    - In the `Permissions` tab click `Add permissions > Create inline policy`
    - Select the `Bedrock` Service and tick `InvokeAgent`
    - Set `Resoureces` to `Specific > Any in this account`  
    - Click `Next`
    - Give the Policy a name and click `Create Policy`
3. From the newly created User click `Create access key`
    - Select `Local code`
    - Click `Next`
    - Click `Create access key`
    - `Access key` is the `AWS_ACCESS_KEY_ID` env var
    - `Secret access key` is the `AWS_SECRET_ACCESS_KEY` env var

NOTE: this is proof of concept only - review the AWS docs for IAM user alternates.

### 2. Create an S3 Bucket and Upload Files
Docs: https://docs.aws.amazon.com/AmazonS3/latest/userguide/create-bucket-overview.html

1. Go to [AWS S3 Console](https://s3.console.aws.amazon.com/s3/home)
2. Create a bucket
3. Upload text files that the AI Chatbot should have access to
    - There are example files in this projects `docs/` directory which can be used

### 3. Request Access to Models
Docs: https://docs.aws.amazon.com/bedrock/latest/userguide/models-supported.html

1. Go to [Bedrock Console](https://console.aws.amazon.com/bedrock)
2. In the sidebar, click `Model catalog`
    - Search for `Titan Text Embeddings V2` and request access
    - Also request access for `Nova Micro`

`Titan Text Embeddings V2` converts S3 documents into vectors for the Knowledge Base to search.

`Nova Micro` is used by the Bedrock Agent to generate natural language answers based on the retrieved content.

### 4: Create a Bedrock Knowledge Base
Docs: https://docs.aws.amazon.com/bedrock/latest/userguide/knowledge-base-create.html

1. Go to [Bedrock Console](https://console.aws.amazon.com/bedrock)
2. In the sidebar, click `Knowledge Bases > Create > Knowledge Base with vector store`
    - Follow the wizard, and leave most options as the default value
    - `IAM permissions`: Create and use a new service role
    - `Query Engine data source`: Amazon S3
    - Click `Next`
    - Click the `Data source > Browse S3` button to link your `S3 Bucket`
    - `Parsing strategy`: Amazon Bedrock default parser
    - Click `Next`
    - `Embeddings model`: Select the `Titan Text Embeddings V2` option
    - `Vector store type`: Amazon OpenSearch Serverless
    - Scroll to bottom and click `Create Knowledge Base`
3. Open the newly created Knowledge Base
    - Click on the Data Source
    - Click `Sync`

### 5: Create a Bedrock Agent
Docs: https://docs.aws.amazon.com/bedrock/latest/userguide/agents-create.html

1. Go to [Bedrock Console](https://console.aws.amazon.com/bedrock)
2. In the sidebar, click `Agents`
3. Create a new Agent
    - Follow the wizard, and leave most options as the default value
    - Select the model `Amazon > Nova Micro`
    - Enter some `Instructions for the Agent`
        - e.g. `You are a helpful and concise assistant. Answer user questions clearly and accurately based only on the information provided in the linked knowledge base ...` 
    - No `Action groups` required
    - `Memory`: Disabled
    - Scroll to the top of the page and click `Save`
    - Scroll back down and add your Knowledge Base
    - Click `Save` again after adding your Knowledge Base
    - Click `Prepare`
    - In the sidebar, click `Agents` and select your newly created Agent
    - Add the Agent ID to the `BEDROCK_AGENT_ID` env var
    - Click `Create Alias`
    - Add the Agents Alias ID to the `BEDROCK_AGENT_ALIAS_ID` env var

## (2) Run App
1. Copy `.env` to `.env.dev` and add your AWS creds from the previous section
2. Open the project and `composer install` then `symfony server:start`
3. Ask a question - e.g. `http://localhost:8000/chatbot/ask?question=What is the support phone number?`

If you uploaded the example files from this projects `docs` directory your response might look like:
```json
{
  "question": "What is the support phone number?",
  "answer": "The support phone number is 1800 000 000. For further assistance, you can also email us at support@example.org. "
}
```

## (3) Calculating AWS Costs

Consider there are multiple AWS products being used here which can incur costs. E.g.
- S3 Bucket
- Amazon Bedrock Knowledge Base
    - Embeddings (Titan Text Embeddings V2)
    - Vector Store (Amazon OpenSearch Serverless)
- Amazon Bedrock Agent
- Amazon Titan Text Express (Nova Micro)


