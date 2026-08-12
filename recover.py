import json

log_file = r"C:\Users\seigi\.gemini\antigravity-ide\brain\1fb2bb91-0f6a-47d9-805f-9bfe897238f4\.system_generated\logs\transcript_full.jsonl"
target_file = r"c:\xampp\htdocs\CitiLife-System\views\pages\radtech\patient-lists.view.php"

# Start with the current file contents (which is the reverted one)
with open(target_file, "r", encoding="utf-8") as f:
    content = f.read()

applied_count = 0

with open(log_file, "r", encoding="utf-8") as f:
    for line in f:
        try:
            data = json.loads(line)
            if data.get("type") == "PLANNER_RESPONSE":
                tool_calls = data.get("tool_calls", [])
                for tc in tool_calls:
                    if tc.get("name") == "multi_replace_file_content":
                        args = tc.get("args", {})
                        if args.get("TargetFile", "").endswith("patient-lists.view.php"):
                            # It's a modification to patient-lists.view.php
                            chunks = args.get("ReplacementChunks", [])
                            # We might have json parsing issues if it's a string
                            if isinstance(chunks, str):
                                chunks = json.loads(chunks)
                            
                            # Apply chunks from bottom to top to avoid line shifting issues
                            # Wait, multi_replace_file_content applies them all. 
                            # But since we just want to re-run them sequentially, we can just do string replacements.
                            for chunk in chunks:
                                target = chunk.get("TargetContent", "")
                                replacement = chunk.get("ReplacementContent", "")
                                if target in content:
                                    content = content.replace(target, replacement)
                                    applied_count += 1
                                else:
                                    # Fallback: maybe just replace by lines?
                                    print(f"Could not find target chunk: {target[:50]}...")
                                    
        except Exception as e:
            pass

with open(r"c:\xampp\htdocs\CitiLife-System\views\pages\radtech\patient-lists_recovered.view.php", "w", encoding="utf-8") as f:
    f.write(content)

print(f"Applied {applied_count} chunks. Recovered file written to patient-lists_recovered.view.php")
